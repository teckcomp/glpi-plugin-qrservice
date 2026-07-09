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
        global $DB;
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $marcasAtuais = array_keys($this->getMarcas());

        echo "<tr class='tab_bg_1'><td colspan='4' style='padding:0;'>";
        echo "<style>
        .qrsc-wrap { display:grid; grid-template-columns:1fr 1fr; gap:18px 28px; padding:20px 14px; }
        @media (max-width:900px) { .qrsc-wrap { grid-template-columns:1fr; } }
        .qrsc-field label.qrsc-label { display:block; font-weight:600; font-size:13px; color:#374151; margin-bottom:6px; }
        .qrsc-field input[type=text] { width:100%; max-width:420px; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; }
        .qrsc-marcas { grid-column:1 / -1; }
        .qrsc-chips { display:flex; flex-wrap:wrap; gap:10px; margin-top:4px; }
        .qrsc-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border:1px solid #d1d5db; border-radius:999px; cursor:pointer; user-select:none; background:#fff; transition:all .15s; font-size:13px; }
        .qrsc-chip:hover { border-color:#1a3a6b; box-shadow:0 1px 4px rgba(26,58,107,.15); }
        .qrsc-chip input { accent-color:#1a3a6b; margin:0; }
        .qrsc-chip.qrsc-on { background:#eef4ff; border-color:#1a3a6b; color:#1a3a6b; font-weight:600; }
        .qrsc-hint { font-size:12px; color:#6b7280; margin-top:8px; }
        .qrsc-header { display:flex; align-items:center; gap:14px; padding:18px 14px 0; }
        .qrsc-logo { width:52px; height:52px; border-radius:12px; background:linear-gradient(135deg,#123a70,#0b1f4d); display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(11,31,77,.35); }
        .qrsc-badge { display:inline-block; margin-top:4px; padding:2px 10px; border-radius:999px; background:#e3f0ff; color:#1a3a6b; font-size:11px; font-weight:700; }
        .qrsc-title { font-size:18px; font-weight:800; color:#1a3a6b; line-height:1.1; }
        .qrsc-sub { font-size:12px; color:#6b7280; }
        </style>";

        echo "<div class='qrsc-header'>";
        echo "<div class='qrsc-logo'>";
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="34" height="34">'
           . '<rect x="12" y="12" width="30" height="30" rx="4" fill="none" stroke="#fff" stroke-width="5"/>'
           . '<rect x="18" y="18" width="18" height="18" rx="2" fill="#4fc3f7"/>'
           . '<rect x="58" y="12" width="30" height="30" rx="4" fill="none" stroke="#fff" stroke-width="5"/>'
           . '<rect x="64" y="18" width="18" height="18" rx="2" fill="#4fc3f7"/>'
           . '<rect x="12" y="58" width="30" height="30" rx="4" fill="none" stroke="#fff" stroke-width="5"/>'
           . '<rect x="18" y="64" width="18" height="18" rx="2" fill="#4fc3f7"/>'
           . '<rect x="48" y="48" width="8" height="8" rx="1" fill="#fff"/>'
           . '<rect x="60" y="48" width="8" height="8" rx="1" fill="#fff"/>'
           . '<rect x="72" y="48" width="8" height="8" rx="1" fill="#4fc3f7"/>'
           . '<rect x="48" y="60" width="8" height="8" rx="1" fill="#4fc3f7"/>'
           . '<rect x="60" y="60" width="8" height="8" rx="1" fill="#fff"/>'
           . '<rect x="72" y="60" width="8" height="8" rx="1" fill="#fff"/>'
           . '<rect x="48" y="72" width="8" height="8" rx="1" fill="#fff"/>'
           . '<rect x="60" y="72" width="8" height="8" rx="1" fill="#4fc3f7"/>'
           . '<rect x="72" y="72" width="8" height="8" rx="1" fill="#fff"/>'
           . '</svg>';
        echo "</div>";
        echo "<div><div class='qrsc-title'>QR Service</div>";
        echo "<div class='qrsc-sub'>" . __('Cadastro de Cliente', 'qrservice') . "</div>";
        echo "<span class='qrsc-badge'>" . __('Versão', 'qrservice') . ": " . PLUGIN_QRSERVICE_VERSION . "</span></div>";
        echo "</div>";

        echo "<div class='qrsc-wrap'>";

        echo "<div class='qrsc-field'>";
        echo "<label class='qrsc-label'>" . __('Nome do cliente', 'qrservice') . "</label>";
        echo "<input type='text' name='name' value='" . htmlspecialchars($this->fields['name'] ?? '') . "'>";
        echo "</div>";

        echo "<div class='qrsc-field'>";
        echo "<label class='qrsc-label'>" . \Entity::getTypeName(1) . "</label>";
        \Entity::dropdown(['value' => $this->fields['entities_id']]);
        echo "</div>";

        echo "<div class='qrsc-field'>";
        echo "<label class='qrsc-label'>" . __('Ativo', 'qrservice') . "</label>";
        \Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</div>";

        echo "<div class='qrsc-field qrsc-marcas'>";
        echo "<label class='qrsc-label'>" . __('Marcas (localizações de topo deste cliente)', 'qrservice') . "</label>";
        echo "<div class='qrsc-chips'>";
        $iterTopo = $DB->request([
            'FROM'  => 'glpi_locations',
            'WHERE' => ['locations_id' => 0],
            'ORDER' => 'name ASC',
        ]);
        $temTopo = false;
        foreach ($iterTopo as $loc) {
            $temTopo = true;
            $on = in_array((int) $loc['id'], $marcasAtuais, true);
            echo "<label class='qrsc-chip" . ($on ? " qrsc-on" : "") . "'>";
            echo "<input type='checkbox' name='marcas[]' value='" . (int) $loc['id'] . "'" . ($on ? " checked" : "")
               . " onchange=\"this.closest('.qrsc-chip').classList.toggle('qrsc-on', this.checked)\">";
            echo htmlspecialchars($loc['name']);
            echo "</label>";
        }
        if (!$temTopo) {
            echo "<em>" . __('Nenhuma localização de topo cadastrada ainda.', 'qrservice') . "</em>";
        }
        echo "</div>";
        echo "<div class='qrsc-hint'>" . __('Clique nas marcas para vincular ou desvincular deste cliente.', 'qrservice') . "</div>";
        echo "</div>";

        echo "</div>";
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }
}
