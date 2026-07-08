<?php

namespace GlpiPlugin\Qrservice;

use CommonDBTM;
use CommonGLPI;
use Session;

class QrCode extends CommonDBTM
{
    public static $rightname = 'plugin_qrservice';

    public static function getTypeName($nb = 0)
    {
        return _n('QR Code', 'QR Codes', $nb, 'qrservice');
    }

    public static function getIcon()
    {
        return 'ti ti-qrcode';
    }

    public static function getMenuContent()
    {
        $menu = [
            'title' => 'QR Service',
            'page'  => '/plugins/qrservice/front/config.php',
            'icon'  => self::getIcon(),
        ];

        $menu['links']['search'] = '/plugins/qrservice/front/config.php';

        if (self::canCreate()) {
            $menu['links']['add'] = '/plugins/qrservice/front/qrcode.form.php';
        }

        return $menu;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Processa upload do logo e retorna o path relativo salvo, ou null.
     */
    private static function processLogoUpload(array $input, int $id): ?string
    {
        if (empty($_FILES['logo_upload']['tmp_name']) || ($_FILES['logo_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $file     = $_FILES['logo_upload'];
        $allowed  = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
        // Limite de 1MB
        if ($file['size'] > 1048576) {
            \Session::addMessageAfterRedirect(
                __('Logo: tamanho máximo permitido é 1MB.', 'qrservice'),
                false, ERROR
            );
            return null;
        }
        $mime     = mime_content_type($file['tmp_name']);

        if (!isset($allowed[$mime])) {
            \Session::addMessageAfterRedirect(
                __('Logo: apenas PNG e JPG são aceitos.', 'qrservice'),
                false, ERROR
            );
            return null;
        }

        $dir = GLPI_ROOT . '/plugins/qrservice/img/logos/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Remove logo antigo se existir
        global $DB;
        $old = $DB->request(['SELECT' => 'logo_path', 'FROM' => self::getTable(), 'WHERE' => ['id' => $id]]);
        foreach ($old as $row) {
            if (!empty($row['logo_path'])) {
                $oldFile = GLPI_ROOT . '/plugins/qrservice/' . $row['logo_path'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
        }

        $ext      = $allowed[$mime];
        $filename = 'logo_' . $id . '_' . time() . '.' . $ext;
        $dest     = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            \Session::addMessageAfterRedirect(
                __('Erro ao salvar o logo. Verifique permissões.', 'qrservice'),
                false, ERROR
            );
            return null;
        }

        chown($dest, 'www-data');
        return 'img/logos/' . $filename;
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['token'])) {
            $input['token'] = self::generateToken();
        }
        if (empty($input['entities_id'])) {
            $input['entities_id'] = Session::getActiveEntity() ?: 0;
        }
        // Logo só pode ser processado após ter o ID — tratado no post-add via hook se necessário.
        // Para add, ignoramos upload (raro criar e já ter logo).
        unset($input['logo_upload']);
        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $logoPath = self::processLogoUpload($input, $this->getID());
        if ($logoPath !== null) {
            $input['logo_path'] = $logoPath;
        }
        unset($input['logo_upload']);
        return $input;
    }

    public static function getByToken(string $token): ?array
    {
        global $DB;

        $result = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'token'     => $token,
                'is_active' => 1,
            ],
            'LIMIT' => 1,
        ]);

        foreach ($result as $row) {
            return $row;
        }
        return null;
    }

    public function getPublicUrl(): string
    {
        global $CFG_GLPI;
        return rtrim($CFG_GLPI['url_base'] ?? '', '/')
            . '/plugins/qrservice/front/formulario.php?token=' . $this->fields['token'];
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof self) {
            $total = count(Campo::getTodosCamposDoQrCode($item->getID()));
            return self::createTabEntry(
                __('Campos do formulário', 'qrservice'),
                $total
            );
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof self) {
            self::showCamposTab($item);
        }
        return true;
    }

    public static function showCamposTab(self $qrcode): void
    {
        $qrcodeID = $qrcode->getID();
        $campos   = Campo::getTodosCamposDoQrCode($qrcodeID);

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th>" . __('Ordem', 'qrservice') . "</th>";
        echo "<th>" . __('Pergunta / Rótulo', 'qrservice') . "</th>";
        echo "<th>" . __('Tipo', 'qrservice') . "</th>";
        echo "<th>" . __('Obrigatório', 'qrservice') . "</th>";
        echo "<th>" . __('Ativo', 'qrservice') . "</th>";
        echo "<th>" . __('Ações', 'qrservice') . "</th>";
        echo "</tr>";

        $tipos = Campo::getTiposDisponiveis();

        if (empty($campos)) {
            echo "<tr class='tab_bg_1'><td colspan='6'>" . __('Nenhum campo cadastrado ainda.', 'qrservice') . "</td></tr>";
        }

        foreach ($campos as $campo) {
            $formUrl = "/plugins/qrservice/front/campo.form.php?id=" . $campo['id'];
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . (int) $campo['ordem'] . "</td>";
            echo "<td><a href='" . $formUrl . "'>" . htmlspecialchars($campo['label']) . "</a></td>";
            echo "<td>" . htmlspecialchars($tipos[$campo['tipo']] ?? $campo['tipo']) . "</td>";
            echo "<td>" . ($campo['obrigatorio'] ? __('Sim', 'qrservice') : __('Não', 'qrservice')) . "</td>";
            echo "<td>" . ($campo['ativo'] ? __('Sim', 'qrservice') : __('Não', 'qrservice')) . "</td>";
            echo "<td><a href='" . $formUrl . "'>" . __('Editar', 'qrservice') . "</a></td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";

        echo "<br>";
        echo "<div class='center'>";
        echo "<a class='vsubmit' href='/plugins/qrservice/front/campo.form.php?plugin_qrservice_qrcodes_id=" . $qrcodeID . "'>";
        echo "+ " . __('Adicionar novo campo', 'qrservice');
        echo "</a>";
        echo "</div>";
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);

        // Força enctype multipart para upload do logo
        $options['enctype'] = 'multipart/form-data';
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Nome do QR Code', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='text' name='name' value='" . htmlspecialchars($this->fields['name'] ?? '') . "'>";
        echo "</td>";
        echo "<td>" . Cliente::getTypeName(1) . "</td>";
        echo "<td>";
        \Dropdown::show(Cliente::class, [
            'name'  => 'plugin_qrservice_clientes_id',
            'value' => $this->fields['plugin_qrservice_clientes_id'],
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Unidade / Filial', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='text' name='unidade' value='" . htmlspecialchars($this->fields['unidade'] ?? '') . "'>";
        echo "</td>";
        echo "<td>" . __('Ativo', 'qrservice') . "</td>";
        echo "<td>";
        \Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Título do formulário público', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='text' name='titulo_formulario' value='" . htmlspecialchars($this->fields['titulo_formulario'] ?? '') . "'>";
        echo "</td>";
        echo "<td>" . __('Subtítulo do formulário público', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='text' name='subtitulo_formulario' value='" . htmlspecialchars($this->fields['subtitulo_formulario'] ?? '') . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Cor primária', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='color' name='cor_primaria' value='"
            . htmlspecialchars($this->fields['cor_primaria'] ?: '#0b1f4d') . "'>";
        echo "</td>";
        echo "<td>" . __('Cor secundária', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='color' name='cor_secundaria' value='"
            . htmlspecialchars($this->fields['cor_secundaria'] ?: '#1a2f6b') . "'>";
        echo "</td>";
        echo "</tr>";

        // --- LINHA DO LOGO ---
        $logoAtual = $this->fields['logo_path'] ?? '';
        // Monta URL absoluta usando window.location no JS para evitar problemas de base_url
        $logoUrl   = !empty($logoAtual) ? $logoAtual : '';

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Logo do formulário', 'qrservice') . "</td>";
        echo "<td colspan='3'>";

        // Preview: mostra logo atual ou placeholder
        echo "<div style='display:flex;align-items:center;gap:16px;flex-wrap:wrap;'>";

        $logoPathJS = htmlspecialchars($logoUrl);
        if (!empty($logoUrl)) {
            echo "<img id='qrs_logo_preview' "
               . "style='height:64px;max-width:200px;object-fit:contain;border:1px solid #ccc;border-radius:4px;padding:4px;background:#fff;'>";
        } else {
            echo "<img id='qrs_logo_preview' "
               . "style='height:64px;max-width:200px;object-fit:contain;border:1px dashed #ccc;border-radius:4px;padding:4px;background:#f9f9f9;display:none;'>";
        }
        if (!empty($logoUrl)) {
            $logoFile = basename($logoPathJS);
            echo "<script>
            (function(){
                var base = window.location.protocol + '//' + window.location.host;
                document.getElementById('qrs_logo_preview').src = base + '/plugins/qrservice/ajax/logo.php?file=" . $logoFile . "&v=' + Date.now();
            })();
            </script>";
        }

        echo "<div>";
        echo "<input type='file' name='logo_upload' id='qrs_logo_input' accept='image/png,image/jpeg' "
           . "style='display:block;margin-bottom:6px;'>";
        echo "<small style='color:#666;'>" . __('PNG ou JPG. Recomendado: fundo transparente, mín. 100×100 px.', 'qrservice') . "</small>";

        if (!empty($logoAtual)) {
            echo "<br><label style='font-size:12px;color:#c00;cursor:pointer;margin-top:4px;display:inline-block;'>";
            echo "<input type='checkbox' name='logo_remover' value='1' style='margin-right:4px;'>";
            echo __('Remover logo atual', 'qrservice');
            echo "</label>";
        }

        echo "</div>";
        echo "</div>";

        // JS: preview em tempo real ao selecionar arquivo
        echo "<script>
        document.getElementById('qrs_logo_input').addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;
            var preview = document.getElementById('qrs_logo_preview');
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
        </script>";

        echo "</td>";
        echo "</tr>";
        // --- FIM LOGO ---

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Usuário técnico padrão (solicitante do chamado)', 'qrservice') . "</td>";
        echo "<td>";
        \User::dropdown([
            'name'  => 'users_id_default_requester',
            'value' => $this->fields['users_id_default_requester'],
        ]);
        echo "</td>";
        echo "<td>" . __('Entidade de destino do chamado', 'qrservice') . "</td>";
        echo "<td>";
        \Entity::dropdown([
            'name'  => 'entities_id_ticket',
            'value' => $this->fields['entities_id_ticket'],
        ]);
        echo "</td>";
        echo "</tr>";

        if ($ID > 0 && !empty($this->fields['token'])) {
            $url         = $this->getPublicUrl();
            $imgUrl      = '/plugins/qrservice/ajax/qrcode-imagem.php?id=' . $ID;
            $downloadUrl = $imgUrl . '&download=1';

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Link público do formulário', 'qrservice') . "</td>";
            echo "<td colspan='3'>";
            echo "<input type='text' style='width:65%' readonly value='" . htmlspecialchars($url) . "'> ";
            echo "<a href='$url' target='_blank'>" . __('Abrir', 'qrservice') . "</a>";
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('QR Code', 'qrservice') . "</td>";
            echo "<td colspan='3'>";
            echo "<img src='" . $imgUrl . "' style='width:200px;height:200px;display:block;margin-bottom:8px;'>";
            echo "<a href='" . $downloadUrl . "' class='vsubmit'>";
            echo "&#11015; " . __('Baixar imagem do QR Code', 'qrservice');
            echo "</a>";
            echo "</td>";
            echo "</tr>";
        }

        $this->showFormButtons($options);

        return true;
    }
}
