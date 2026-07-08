<?php

namespace GlpiPlugin\Qrservice;

use CommonDBTM;

/**
 * Campo customizado exibido no formulário público de um QR Code.
 * Cada QR Code tem seu próprio conjunto de campos (independentes entre si).
 */
class Campo extends CommonDBTM
{
    public static $rightname = 'plugin_qrservice';

    // Tipos de campo suportados pelo formulário público
    public const TIPO_TEXTO    = 'text';
    public const TIPO_TEXTAREA = 'textarea';

    public static function getTypeName($nb = 0)
    {
        return _n('Campo do formulário', 'Campos do formulário', $nb, 'qrservice');
    }

    public static function getIcon()
    {
        return 'ti ti-forms';
    }

    /**
     * Retorna os campos ativos de um QR Code, na ordem definida
     */
    public static function getCamposDoQrCode(int $qrcode_id): array
    {
        return (new self())->find([
            'plugin_qrservice_qrcodes_id' => $qrcode_id,
            'ativo'                       => 1,
        ], ['ordem ASC']);
    }

    /**
     * Retorna TODOS os campos de um QR Code (inclusive inativos),
     * para a tela administrativa de gerenciamento.
     */
    public static function getTodosCamposDoQrCode(int $qrcode_id): array
    {
        return (new self())->find([
            'plugin_qrservice_qrcodes_id' => $qrcode_id,
        ], ['ordem ASC']);
    }

    public static function getTiposDisponiveis(): array
    {
        return [
            self::TIPO_TEXTO    => __('Texto curto', 'qrservice'),
            self::TIPO_TEXTAREA => __('Texto longo', 'qrservice'),
        ];
    }

    public function prepareInputForAdd($input)
    {
        // Se não vier ordem definida, joga pro final da lista
        if (empty($input['ordem'])) {
            $ultimaOrdem = 0;
            $existentes = self::getTodosCamposDoQrCode((int) ($input['plugin_qrservice_qrcodes_id'] ?? 0));
            foreach ($existentes as $c) {
                $ultimaOrdem = max($ultimaOrdem, (int) $c['ordem']);
            }
            $input['ordem'] = $ultimaOrdem + 1;
        }
        return $input;
    }

    public function showForm($ID, array $options = [])
    {
        $qrcodeID = (int) ($options['plugin_qrservice_qrcodes_id'] ?? $this->fields['plugin_qrservice_qrcodes_id'] ?? 0);

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<input type='hidden' name='plugin_qrservice_qrcodes_id' value='" . $qrcodeID . "'>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Pergunta / Rótulo', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='text' name='label' style='width:95%' value='" . htmlspecialchars($this->fields['label'] ?? '') . "'>";
        echo "</td>";
        echo "<td>" . __('Tipo de resposta', 'qrservice') . "</td>";
        echo "<td>";
        \Dropdown::showFromArray('tipo', self::getTiposDisponiveis(), [
            'value' => $this->fields['tipo'] ?? self::TIPO_TEXTO,
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Obrigatório', 'qrservice') . "</td>";
        echo "<td>";
        \Dropdown::showYesNo('obrigatorio', $this->fields['obrigatorio'] ?? 0);
        echo "</td>";
        echo "<td>" . __('Ordem de exibição', 'qrservice') . "</td>";
        echo "<td>";
        echo "<input type='number' name='ordem' style='width:80px' value='" . (int) ($this->fields['ordem'] ?? 0) . "'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Ativo', 'qrservice') . "</td>";
        echo "<td>";
        \Dropdown::showYesNo('ativo', $this->fields['ativo'] ?? 1);
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }
}
