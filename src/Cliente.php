<?php

namespace GlpiPlugin\Qrservice;

use CommonDBTM;
use Session;

/**
 * Representa um Cliente (empresa) — agrupador organizacional.
 * Um cliente pode ter vários QR Codes (ex: "Recepção", "TI Interno").
 *
 * A "Localização raiz" define até onde a árvore de Localizações do GLPI
 * fica visível no formulário público desse cliente — garante isolamento
 * entre clientes diferentes (ex: BV nunca vê unidades da Rede Guest).
 */
class Cliente extends CommonDBTM
{
    public static $rightname = 'plugin_qrservice';

    public static function getTypeName($nb = 0)
    {
        return _n('Cliente', 'Clientes', $nb, 'qrservice');
    }

    public static function getMenuContent()
    {
        $menu = [
            'title' => self::getTypeName(2),
            'page'  => '/plugins/qrservice/front/cliente.php',
            'icon'  => self::getIcon(),
        ];

        if (self::canCreate()) {
            $menu['links']['add'] = '/plugins/qrservice/front/cliente.form.php';
        }
        if (self::canView()) {
            $menu['links']['search'] = '/plugins/qrservice/front/cliente.php';
        }

        return $menu;
    }

    public static function getIcon()
    {
        return 'ti ti-building';
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['entities_id'])) {
            $input['entities_id'] = Session::getActiveEntity() ?: 0;
        }
        return $input;
    }

    /**
     * Nível 2: filhos diretos da raiz (ex: "Marca/Unidade").
     * Lista pequena (~125), pode ser carregada de uma vez.
     */
    /**
     * Marcas (localizações de topo) vinculadas a este cliente.
     * @return array [locations_id => nome]
     */
    public function getMarcas(): array
    {
        global $DB;
        $out = [];
        $iterator = $DB->request([
            'SELECT'     => ['glpi_locations.id', 'glpi_locations.name'],
            'FROM'       => 'glpi_plugin_qrservice_clientes_marcas',
            'INNER JOIN' => [
                'glpi_locations' => [
                    'ON' => [
                        'glpi_locations'                        => 'id',
                        'glpi_plugin_qrservice_clientes_marcas' => 'locations_id',
                    ],
                ],
            ],
            'WHERE' => ['plugin_qrservice_clientes_id' => (int) $this->getID()],
            'ORDER' => 'glpi_locations.name ASC',
        ]);
        foreach ($iterator as $row) {
            $out[(int) $row['id']] = $row['name'];
        }
        return $out;
    }

    /**
     * Regrava os vínculos Cliente <-> Marcas.
     */
    public static function syncMarcas(int $clienteID, array $locIDs): void
    {
        global $DB;
        $DB->delete('glpi_plugin_qrservice_clientes_marcas', [
            'plugin_qrservice_clientes_id' => $clienteID,
        ]);
        foreach ($locIDs as $locID) {
            $locID = (int) $locID;
            if ($locID > 0) {
                $DB->insert('glpi_plugin_qrservice_clientes_marcas', [
                    'plugin_qrservice_clientes_id' => $clienteID,
                    'locations_id'                 => $locID,
                ]);
            }
        }
    }

    public function getAssociados(): array
    {
        global $DB;

        $raizID = (int) ($this->fields['locations_id_raiz'] ?? 0);
        if ($raizID <= 0) {
            return [];
        }

        $resultado = [];
        $iterator = $DB->request([
            'FROM'  => 'glpi_locations',
            'WHERE' => ['locations_id' => $raizID],
            'ORDER' => 'name ASC',
        ]);

        foreach ($iterator as $row) {
            $resultado[$row['id']] = $row['name'];
        }

        return $resultado;
    }

    /**
     * Nível 3: filhos diretos de uma Marca/Unidade (ex: "Localização").
     * Pode ser uma lista grande — usado sob demanda via AJAX.
     */
    /**
     * Detecta o modo de localização quando configurado como Automático:
     * 1 = cascata (raiz tem netos), 2 = dropdown simples (só filhos), 3 = sem localização
     */
    public function getModoAutomatico(): int
    {
        global $DB;
        $associados = $this->getAssociados();
        if (empty($associados)) {
            return 3;
        }
        $iterator = $DB->request([
            'FROM'  => 'glpi_locations',
            'WHERE' => ['locations_id' => array_keys($associados)],
            'LIMIT' => 1,
        ]);
        foreach ($iterator as $row) {
            return 1;
        }
        return 2;
    }

    public static function getLojasDoAssociado(int $associadoID): array
    {
        global $DB;

        $resultado = [];
        $iterator = $DB->request([
            'FROM'  => 'glpi_locations',
            'WHERE' => ['locations_id' => $associadoID],
            'ORDER' => 'name ASC',
        ]);

        foreach ($iterator as $row) {
            $resultado[] = ['id' => (int) $row['id'], 'name' => $row['name']];
        }

        return $resultado;
    }

    /**
     * Retorna o endereço cadastrado de uma Localização específica.
     */
    public static function getEnderecoDaLoja(int $lojaID): string
    {
        $loja = new \Location();
        if ($loja->getFromDB($lojaID)) {
            return (string) ($loja->fields['address'] ?? '');
        }
        return '';
    }
    /**
     * A localização pertence à árvore de ALGUMA das marcas deste cliente?
     */
    public function localizacaoPertenceAoCliente(int $locationID): bool
    {
        if ($locationID <= 0) {
            return false;
        }
        $marcas = array_keys($this->getMarcas());
        if (empty($marcas)) {
            return false;
        }
        if (in_array($locationID, $marcas, true)) {
            return true;
        }
        $alvo = new \Location();
        if (!$alvo->getFromDB($locationID)) {
            return false;
        }
        foreach ($marcas as $marcaID) {
            $marca = new \Location();
            if ($marca->getFromDB((int) $marcaID)
                && str_starts_with($alvo->fields['completename'], $marca->fields['completename'] . ' > ')) {
                return true;
            }
        }
        return false;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Nome do cliente', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='text' name='name' value='" . htmlspecialchars($this->fields['name'] ?? '') . "'>";
        echo "</td>";
        echo "<td>" . \Entity::getTypeName(1) . "</td>";
        echo "<td>";
        \Entity::dropdown(['value' => $this->fields['entities_id']]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Ativo', 'qrservice') . "</td>";
        echo "<td>";
        \Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</td>";
        echo "<td>" . __('Marcas (localizações de topo deste cliente)', 'qrservice')
            . "<br><small style='color:#e67e00;font-weight:700;'>"
            . __('Segure Ctrl (ou Cmd) para selecionar várias', 'qrservice') . "</small></td>";
        echo "<td>";
        global $DB;
        $marcasAtuais = array_keys($this->getMarcas());
        echo "<select name='marcas[]' multiple size='8' style='min-width:260px;'>";
        $iterTopo = $DB->request([
            'FROM'  => 'glpi_locations',
            'WHERE' => ['locations_id' => 0],
            'ORDER' => 'name ASC',
        ]);
        foreach ($iterTopo as $loc) {
            $sel = in_array((int) $loc['id'], $marcasAtuais, true) ? ' selected' : '';
            echo "<option value='" . (int) $loc['id'] . "'$sel>" . htmlspecialchars($loc['name']) . "</option>";
        }
        echo "</select>";
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }
}
