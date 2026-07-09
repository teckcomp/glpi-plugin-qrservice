<?php

use GlpiPlugin\Qrservice\QrCode;
use GlpiPlugin\Qrservice\Cliente;
use GlpiPlugin\Qrservice\Campo;

// -------------------------------------------------------------------
// Bootstrap do framework GLPI. Este arquivo é intencionalmente PUBLICO:
// NAO chamamos Session::checkLoginUser() em nenhum ponto.
// -------------------------------------------------------------------
include('../../../inc/includes.php');

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    Html::displayErrorAndDie(__('Link inválido: token não informado.', 'qrservice'));
}

$qrcodeData = QrCode::getByToken($token);
if ($qrcodeData === null) {
    Html::displayErrorAndDie(__('Formulário não encontrado ou inativo.', 'qrservice'));
}

$cliente = new Cliente();
$cliente->getFromDB((int) $qrcodeData['plugin_qrservice_clientes_id']);

$marcas     = $cliente->getMarcas();
$campos     = Campo::getCamposDoQrCode((int) $qrcodeData['id']);

$modoLocalizacao = ((int) ($qrcodeData['modo_localizacao'] ?? 0) === 3) ? 3 : 0;
if (empty($marcas)) {
    $modoLocalizacao = 3; // cliente sem marcas cadastradas -> sem localização
}
$erros      = [];
$ticketID   = null;

// -------------------------------------------------------------------
// Anti-spam: captcha matemático simples (sem dependência externa)
// -------------------------------------------------------------------
function qrservice_gerar_captcha(): array
{
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['qrservice_captcha_resposta'] = $a + $b;
    return ['a' => $a, 'b' => $b];
}

function qrservice_tem_filhos(int $locID): bool
{
    global $DB;
    foreach ($DB->request(['FROM' => 'glpi_locations', 'WHERE' => ['locations_id' => $locID], 'LIMIT' => 1]) as $r) {
        return true;
    }
    return false;
}

// -------------------------------------------------------------------
// Processamento do envio (POST)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qrservice_submit'])) {

    $nome        = trim($_POST['nome'] ?? '');
    $telefone    = trim($_POST['telefone'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $marcaID     = (int) ($_POST['marca'] ?? 0);
    $unidadeID   = (int) ($_POST['unidade'] ?? 0);
    $locationID  = (int) ($_POST['localizacao'] ?? 0);
    $endereco    = trim($_POST['endereco'] ?? '');
    $descricao   = trim($_POST['descricao'] ?? '');

    if ($nome === '') {
        $erros[] = __('Informe seu nome completo.', 'qrservice');
    }
    if ($telefone === '') {
        $erros[] = __('Informe o telefone para contato.', 'qrservice');
    }
    if ($email === '') {
        $erros[] = __('Informe o e-mail para contato.', 'qrservice');
    }
    if ($modoLocalizacao !== 3) {
        if ($marcaID <= 0 || !array_key_exists($marcaID, $marcas)) {
            $erros[] = __('Selecione a Marca.', 'qrservice');
        } elseif (qrservice_tem_filhos($marcaID)) {
            $uniObj = new Location();
            if ($unidadeID <= 0 || !$uniObj->getFromDB($unidadeID) || (int) $uniObj->fields['locations_id'] !== $marcaID) {
                $erros[] = __('Selecione a Unidade.', 'qrservice');
            } elseif (qrservice_tem_filhos($unidadeID)) {
                $locObj = new Location();
                if ($locationID <= 0 || !$locObj->getFromDB($locationID) || (int) $locObj->fields['locations_id'] !== $unidadeID) {
                    $erros[] = __('Selecione a Localização.', 'qrservice');
                }
            }
        }
    }
    if ($endereco === '') {
        $erros[] = __('Informe o endereço.', 'qrservice');
    }
    if ($descricao === '') {
        $erros[] = __('Descreva o problema.', 'qrservice');
    }

    // Localização final = nível mais específico selecionado
    $localFinal = $locationID > 0 ? $locationID : ($unidadeID > 0 ? $unidadeID : $marcaID);
    if ($modoLocalizacao !== 3 && $localFinal > 0 && !$cliente->localizacaoPertenceAoCliente($localFinal)) {
        $erros[] = __('Localização inválida para este formulário.', 'qrservice');
    }

    $respostasCustom = [];
    foreach ($campos as $campo) {
        $chave = 'campo_' . $campo['id'];
        $valor = trim($_POST[$chave] ?? '');
        if ($campo['obrigatorio'] && $valor === '') {
            $erros[] = sprintf(__('O campo "%s" é obrigatório.', 'qrservice'), $campo['label']);
        }
        if ($valor !== '') {
            $respostasCustom[] = $campo['label'] . ': ' . $valor;
        }
    }

    if (empty($qrcodeData['users_id_default_requester'])) {
        $erros[] = __('Este formulário ainda não foi configurado por completo (usuário técnico padrão ausente). Contate o administrador.', 'qrservice');
    }

    // Honeypot: campo invisível que só um robô preencheria
    if (trim($_POST['site_contato'] ?? '') !== '') {
        $erros[] = __('Não foi possível validar o envio.', 'qrservice');
    }

    // Captcha matemático
    $captchaEnviado  = (int) ($_POST['captcha_resposta'] ?? -1);
    $captchaEsperado = (int) ($_SESSION['qrservice_captcha_resposta'] ?? -999);
    if ($captchaEnviado !== $captchaEsperado) {
        $erros[] = __('Resposta da verificação incorreta. Tente novamente.', 'qrservice');
    }

    // Validação do anexo (opcional, máx. 2MB, extensões permitidas)
    $limiteAnexoBytes = 2 * 1024 * 1024;
    $extensoesPermitidas = ['png', 'jpeg', 'jpg', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    if (!empty($_FILES['anexo']['name'])) {
        if ($_FILES['anexo']['error'] !== UPLOAD_ERR_OK) {
            $erros[] = __('Erro ao enviar o anexo. Tente novamente.', 'qrservice');
        } elseif ($_FILES['anexo']['size'] > $limiteAnexoBytes) {
            $erros[] = __('O anexo excede o limite de 2MB.', 'qrservice');
        } else {
            $extensao = strtolower(pathinfo($_FILES['anexo']['name'], PATHINFO_EXTENSION));
            if (!in_array($extensao, $extensoesPermitidas, true)) {
                $erros[] = __('Tipo de arquivo não permitido. Envie PNG, JPEG, JPG, PDF, DOC, DOCX, XLS ou XLSX.', 'qrservice');
            }
        }
    }

    if (empty($erros)) {
        global $DB;
        $auth = new Auth();
        $auth->auth_succeded = true;
        $auth->user = new User();
        $auth->user->getFromDB((int) $qrcodeData['users_id_default_requester']);
        Session::init($auth);

        try {

        $entidadeDestino = $qrcodeData['entities_id_ticket'] ?: $qrcodeData['entities_id'];
        Session::changeActiveEntities($entidadeDestino, true);

        // -----------------------------------------------------------
        // Tenta identificar um usuário GLPI já existente: mesma
        // Localização (escolhida no dropdown) + Telefone OU E-mail
        // batendo com o cadastro. Se achar, ele vira o requerente real
        // do chamado (em vez do usuário técnico genérico do QR Code).
        // -----------------------------------------------------------
        $usuarioIdentificadoID = null;

        // -------------------------------------------------------------
        // PRIORIDADE 1: existe algum usuário vinculado a esta Marca/Unidade
        // (ou a qualquer nível abaixo dela na árvore)? Muitos ambientes
        // cadastram um usuário "representando" a própria unidade (ex:
        // "Biotec Filho 01"), então esse vínculo já basta — não é
        // necessário bater telefone/e-mail nesse caso.
        // -------------------------------------------------------------
        // Busca em camadas: Localização -> Unidade -> Marca
        foreach ([$locationID, $unidadeID, $marcaID] as $nivelID) {
            if ($usuarioIdentificadoID !== null || $nivelID <= 0) {
                continue;
            }
            $nivelLoc = new Location();
            if (!$nivelLoc->getFromDB($nivelID)) {
                continue;
            }
            $iteratorPorLocal = $DB->request([
                'SELECT'    => ['glpi_users.id'],
                'FROM'      => 'glpi_users',
                'LEFT JOIN' => [
                    'glpi_locations' => [
                        'ON' => [
                            'glpi_locations' => 'id',
                            'glpi_users'     => 'locations_id',
                        ],
                    ],
                ],
                'WHERE' => [
                    'glpi_users.is_active'  => 1,
                    'glpi_users.is_deleted' => 0,
                    'OR' => [
                        'glpi_locations.completename' => $nivelLoc->fields['completename'],
                        ['glpi_locations.completename' => ['LIKE', $nivelLoc->fields['completename'] . ' > %']],
                    ],
                ],
                'LIMIT' => 1,
            ]);
            foreach ($iteratorPorLocal as $linha) {
                $usuarioIdentificadoID = (int) $linha['id'];
            }
        }

        // -------------------------------------------------------------
        // PRIORIDADE 2: se não achou ninguém vinculado à Marca/Unidade,
        // tenta achar uma pessoa física pelo Telefone OU E-mail informado,
        // sem exigir vínculo de Localização.
        // -------------------------------------------------------------
        if ($usuarioIdentificadoID === null) {
            $iteratorPorContato = $DB->request([
                'SELECT'    => ['glpi_users.id'],
                'FROM'      => 'glpi_users',
                'LEFT JOIN' => [
                    'glpi_useremails' => [
                        'ON' => [
                            'glpi_useremails' => 'users_id',
                            'glpi_users'      => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    'glpi_users.is_active'  => 1,
                    'glpi_users.is_deleted' => 0,
                    'OR' => [
                        'glpi_users.phone'      => $telefone,
                        'glpi_useremails.email' => $email,
                    ],
                ],
                'LIMIT' => 1,
            ]);

            foreach ($iteratorPorContato as $linha) {
                $usuarioIdentificadoID = (int) $linha['id'];
            }
        }

        $requerenteID = $usuarioIdentificadoID ?? (int) $qrcodeData['users_id_default_requester'];

        $conteudo  = "Nome do solicitante: $nome\n";
        $conteudo .= "Telefone: $telefone\n";
        $conteudo .= "E-mail: $email\n";
        $conteudo .= "Endereço: $endereco\n";
        if (!empty($respostasCustom)) {
            $conteudo .= "\n" . implode("\n", $respostasCustom) . "\n";
        }
        $conteudo .= "\nDescrição:\n" . $descricao;

        $inputTicket = [
            'name'                  => 'Chamado via QR - ' . $qrcodeData['name'] . ' - ' . $nome,
            'content'               => $conteudo,
            'entities_id'           => $entidadeDestino,
            'locations_id'          => ($modoLocalizacao === 3) ? 0 : $localFinal,
            '_users_id_requester'   => $requerenteID,
            'urgency'               => 3,
            'impact'                => 3,
            'priority'              => 3,
            '_skip_promoted_fields' => true,
        ];

        // -----------------------------------------------------------
        // Anexo: usamos o mesmo mecanismo que o GLPI usa internamente
        // para anexar arquivos de e-mail recebidos (MailCollector) —
        // o arquivo já vai junto no Ticket::add(), via '_filename'/'_tag'.
        // -----------------------------------------------------------
        $nomeArquivoTmp = null;
        if (!empty($_FILES['anexo']['name']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
            $nomeOriginal   = basename($_FILES['anexo']['name']);
            $nomeArquivoTmp = uniqid('qrservice_', true) . '_' . $nomeOriginal;
            $caminhoTemp    = GLPI_TMP_DIR . '/' . $nomeArquivoTmp;

            if (move_uploaded_file($_FILES['anexo']['tmp_name'], $caminhoTemp)) {
                $inputTicket['_filename'] = [$nomeArquivoTmp];
                $inputTicket['_tag']      = [uniqid('qrservicetag_', true)];
            } else {
                \Toolbox::logInFile(
                    'qrservice',
                    'Falha ao mover arquivo temporario do anexo - arquivo: ' . $nomeOriginal
                );
                $nomeArquivoTmp = null;
            }
        }

        // Origem da requisição: QR Code
        $rtQr = new RequestType();
        if ($rtQr->getFromDBByCrit(['name' => 'QR Code'])) {
            $inputTicket['requesttypes_id'] = $rtQr->getID();
        }

        $ticket = new Ticket();
        $ticketID = $ticket->add($inputTicket);

        if ($ticketID) {
            // Deixa o nome do anexo mais limpo (sem o prefixo único interno)
            if ($nomeArquivoTmp !== null) {
                $docItem = new Document_Item();
                $vinculos = $docItem->find([
                    'itemtype' => Ticket::class,
                    'items_id' => $ticketID,
                ]);
                foreach ($vinculos as $vinculo) {
                    $doc = new Document();
                    if ($doc->getFromDB($vinculo['documents_id'])) {
                        $doc->update([
                            'id'   => $doc->getID(),
                            'name' => $nomeOriginal ?? $doc->fields['name'],
                        ]);
                    }
                }
            }

            global $DB;
            $DB->insert('glpi_plugin_qrservice_chamados', [
                'plugin_qrservice_qrcodes_id' => (int) $qrcodeData['id'],
                'tickets_id'                  => $ticketID,
                'nome_solicitante'            => $nome,
                'telefone_solicitante'        => $telefone,
                'date_creation'               => date('Y-m-d H:i:s'),
            ]);
        }

        } finally {
            Session::destroy();
            session_write_close();
        }

        include(__DIR__ . '/../templates/sucesso.php');
        exit;
    }
}

// Sempre gera um captcha NOVO antes de exibir o formulário
// (seja no primeiro carregamento, seja após um erro de validação)
$captcha = qrservice_gerar_captcha();

include(__DIR__ . '/../templates/formulario.php');
