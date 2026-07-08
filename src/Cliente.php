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
     * Segurança: confirma que um determinado ID de Localização realmente
     * pertence à árvore raiz deste cliente (evita acessar dados de outro
     * cliente adulterando o parâmetro da requisição AJAX).
     */
    public function localizacaoPertenceAoCliente(int $locationID): bool
    {
        global $DB;

        $raizID = (int) ($this->fields['locations_id_raiz'] ?? 0);
        if ($raizID <= 0 || $locationID <= 0) {
            return false;
        }

        $raiz = new \Location();
        if (!$raiz->getFromDB($raizID)) {
            return false;
        }

        if ($locationID === $raizID) {
            return true;
        }

        $alvo = new \Location();
        if (!$alvo->getFromDB($locationID)) {
            return false;
        }

        $completenameRaiz = $raiz->fields['completename'];
        return str_starts_with($alvo->fields['completename'], $completenameRaiz . ' > ');
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
        echo "<td>" . __('Localização raiz (Grupo — isola a árvore deste cliente)', 'qrservice') . "</td>";
        echo "<td>";
        \Location::dropdown([
            'name'  => 'locations_id_raiz',
            'value' => $this->fields['locations_id_raiz'] ?? 0,
        ]);
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }
}
