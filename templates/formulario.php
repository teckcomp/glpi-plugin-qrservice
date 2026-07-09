<?php
/**
 * Variáveis: $qrcodeData, $cliente, $associados, $campos, $erros, $token, $captcha
 */
$corPrimaria   = htmlspecialchars($qrcodeData['cor_primaria'] ?: '#0b1f4d');
$corSecundaria = htmlspecialchars($qrcodeData['cor_secundaria'] ?: '#1a2f6b');
$titulo        = htmlspecialchars($qrcodeData['titulo_formulario'] ?: 'Abertura de Chamado');
$subtitulo     = htmlspecialchars($qrcodeData['subtitulo_formulario'] ?: 'Suporte Técnico');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $titulo; ?></title>
<style>
    :root {
        --qr-cor-primaria: <?php echo $corPrimaria; ?>;
        --qr-cor-secundaria: <?php echo $corSecundaria; ?>;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        background: linear-gradient(180deg, var(--qr-cor-primaria) 0%, var(--qr-cor-secundaria) 100%); background-attachment: fixed;
        min-height: 100vh;
    }
    .qr-header { background: linear-gradient(90deg, var(--qr-cor-primaria), var(--qr-cor-secundaria)); padding: 24px 20px; text-align: center; }
    .qr-header img { max-height: 60px; }
    .qr-title-wrap { text-align: center; padding: 28px 20px 10px; color: #fff; }
    .qr-title-wrap h1 { margin: 0 0 6px; font-size: 26px; }
    .qr-title-wrap p { margin: 0; opacity: .8; }
    .qr-card { background: #fff; border-radius: 18px; max-width: 520px; margin: 20px auto 60px; padding: 28px 24px; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    .qr-field { margin-bottom: 18px; }
    .qr-field label { display: block; font-weight: 700; color: var(--qr-cor-secundaria); margin-bottom: 6px; font-size: 15px; }
    .qr-field input[type=text], .qr-field input[type=tel], .qr-field input[type=email], .qr-field select, .qr-field textarea {
        width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #d9dde3; background: #f4f6f8; font-size: 15px;
    }
    .qr-field select:disabled { opacity: .5; }
    .qr-field textarea { min-height: 110px; resize: vertical; }
    .qr-submit { width: 100%; padding: 16px; border: none; border-radius: 12px; background: var(--qr-cor-primaria); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; }
    .qr-erros { background: #fdeaea; border: 1px solid #f5b5b5; color: #a12727; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; }
    .qr-erros ul { margin: 4px 0 0; padding-left: 18px; }

    .qr-dropzone {
        border: 2px dashed #c9cfd8;
        border-radius: 12px;
        text-align: center;
        padding: 26px 16px;
        background: #f9fafb;
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .qr-dropzone:hover,
    .qr-dropzone.qr-dropzone--over {
        border-color: var(--qr-cor-primaria);
        background: #f0f3fb;
    }
    .qr-dropzone .qr-dropzone-icon {
        font-size: 26px;
        margin-bottom: 6px;
    }
    .qr-dropzone .qr-dropzone-texto {
        font-weight: 600;
        color: var(--qr-cor-secundaria);
        font-size: 14px;
    }
    .qr-dropzone .qr-dropzone-sub {
        font-size: 12px;
        color: #888;
        margin-top: 4px;
    }
    .qr-dropzone-arquivo {
        margin-top: 10px;
        font-size: 13px;
        font-weight: 600;
        color: var(--qr-cor-primaria);
        display: none;
    }
    .qr-dropzone input[type=file] { display: none; }
</style>
</head>
<body>

<div class="qr-title-wrap" style="padding-top:32px;">
    <?php if (!empty($qrcodeData['logo_path'])): ?>
        <img src="../ajax/logo.php?file=<?php echo urlencode(basename($qrcodeData['logo_path'])); ?>"
             alt="logo" style="max-height:120px;max-width:220px;object-fit:contain;margin-bottom:16px;display:block;margin-left:auto;margin-right:auto;">
    <?php endif; ?>
    <h1><?php echo $titulo; ?></h1>
    <p><?php echo $subtitulo; ?></p>
</div>

<div class="qr-card">

    <?php if (!empty($erros)): ?>
        <div class="qr-erros">
            <strong>Corrija os itens abaixo:</strong>
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?php echo htmlspecialchars($erro); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" id="qrform" enctype="multipart/form-data">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

        <!-- Honeypot anti-spam: campo invisível para humanos, visível para a maioria dos bots -->
        <div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
            <label>Não preencha este campo</label>
            <input type="text" name="site_contato" tabindex="-1" autocomplete="off">
        </div>

        <div class="qr-field">
            <label>Nome completo</label>
            <input type="text" name="nome" placeholder="Seu nome"
                   value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
        </div>

        <div class="qr-field">
            <label>Telefone para contato</label>
            <input type="tel" name="telefone" placeholder="(00) 00000-0000"
                   value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
        </div>

        <div class="qr-field">
            <label>E-mail para contato</label>
            <input type="email" name="email" placeholder="seuemail@exemplo.com"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <?php if ($modoLocalizacao === 1): ?>
        <div class="qr-field">
            <label>Marca / Unidade</label>
            <select name="marca_unidade" id="marca_unidade">
                <option value="">-----</option>
                <?php foreach ($associados as $id => $nome): ?>
                    <option value="<?php echo $id; ?>" <?php echo (($_POST['marca_unidade'] ?? '') == $id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($nome); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="qr-field">
            <label>Localização</label>
            <select name="localizacao" id="localizacao" disabled>
                <option value="">Selecione a Marca/Unidade primeiro</option>
            </select>
        </div>
        <?php elseif ($modoLocalizacao === 2): ?>
        <div class="qr-field">
            <label>Localização</label>
            <select name="localizacao" id="localizacao">
                <option value="">-----</option>
                <?php foreach ($associados as $id => $nome): ?>
                    <option value="<?php echo $id; ?>" <?php echo (($_POST['localizacao'] ?? '') == $id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($nome); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="qr-field">
            <label>Endereço</label>
            <input type="text" name="endereco" id="endereco" placeholder="Endereço completo"
                   value="<?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?>">
        </div>

        <?php foreach ($campos as $campo): ?>
            <div class="qr-field">
                <label><?php echo htmlspecialchars($campo['label']); ?><?php echo $campo['obrigatorio'] ? ' *' : ''; ?></label>
                <?php if ($campo['tipo'] === 'textarea'): ?>
                    <textarea name="campo_<?php echo $campo['id']; ?>"><?php echo htmlspecialchars($_POST['campo_' . $campo['id']] ?? ''); ?></textarea>
                <?php else: ?>
                    <input type="text" name="campo_<?php echo $campo['id']; ?>"
                           value="<?php echo htmlspecialchars($_POST['campo_' . $campo['id']] ?? ''); ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="qr-field">
            <label>Descreva o problema</label>
            <textarea name="descricao" placeholder="Explique o que está acontecendo..."><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
        </div>

        <div class="qr-field">
            <label>Anexo (opcional, máx. 2MB)</label>
            <div class="qr-dropzone" id="qr-dropzone">
                <div class="qr-dropzone-icon">📎</div>
                <div class="qr-dropzone-texto">Arraste e solte o arquivo aqui, ou <u>escolha um arquivo</u></div>
                <div class="qr-dropzone-sub">PNG, JPG, PDF, DOC ou XLS — máx. 2MB</div>
                <div class="qr-dropzone-arquivo" id="qr-dropzone-arquivo"></div>
                <input type="file" name="anexo" id="qr-anexo-input" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx">
            </div>
        </div>

        <div class="qr-field">
            <label>Verificação: quanto é <?php echo (int) $captcha['a']; ?> + <?php echo (int) $captcha['b']; ?>?</label>
            <input type="text" name="captcha_resposta" inputmode="numeric" placeholder="Digite o resultado" autocomplete="off">
        </div>

        <button type="submit" name="qrservice_submit" value="1" class="qr-submit">Enviar chamado</button>
    </form>
</div>

<script>
(function () {
    var token = <?php echo json_encode($token); ?>;
    var ajaxUrl = '../ajax/localizacoes.php';

    var selectMarca = document.getElementById('marca_unidade');
    var selectLocal = document.getElementById('localizacao');
    var inputEndereco = document.getElementById('endereco');
    var localizacaoPreSelecionada = <?php echo json_encode($_POST['localizacao'] ?? ''); ?>;

    // Campos progressivos: Localização aparece após a Marca/Unidade;
    // Endereço aparece após a Localização (modo "sem localização": sempre visível)
    var campoLocal = selectLocal ? selectLocal.closest('.qr-field') : null;
    var campoEndereco = inputEndereco ? inputEndereco.closest('.qr-field') : null;

    function atualizarVisibilidade() {
        if (campoLocal && selectMarca) {
            campoLocal.style.display = selectMarca.value ? '' : 'none';
        }
        if (campoEndereco && selectLocal) {
            campoEndereco.style.display = selectLocal.value ? '' : 'none';
        }
    }

    if (selectLocal) {
        selectLocal.addEventListener('change', atualizarVisibilidade);
    }

    function carregarLojas(marcaID, preselecionarID) {
        selectLocal.innerHTML = '<option value="">Carregando...</option>';
        selectLocal.disabled = true;
        if (!preselecionarID) {
            inputEndereco.value = '';
        }

        if (!marcaID) {
            selectLocal.innerHTML = '<option value="">Selecione a Marca/Unidade primeiro</option>';
            return;
        }

        fetch(ajaxUrl + '?token=' + encodeURIComponent(token) + '&action=lojas&parent=' + encodeURIComponent(marcaID))
            .then(function (r) { return r.json(); })
            .then(function (lista) {
                selectLocal.innerHTML = '<option value="">-----</option>';
                lista.forEach(function (item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    if (preselecionarID && String(item.id) === String(preselecionarID)) {
                        opt.selected = true;
                    }
                    selectLocal.appendChild(opt);
                });
                selectLocal.disabled = false;
                atualizarVisibilidade();
            })
            .catch(function () {
                selectLocal.innerHTML = '<option value="">Erro ao carregar</option>';
            });
    }

    if (selectMarca) {
        selectMarca.addEventListener('change', function () {
            carregarLojas(this.value, null);
            atualizarVisibilidade();
        });
        // Recarregou com erro e já havia Marca/Unidade: recarrega e re-seleciona
        if (selectMarca.value) {
            carregarLojas(selectMarca.value, localizacaoPreSelecionada);
        }
    }
    atualizarVisibilidade();


    // Endereço agora é sempre preenchido manualmente pelo visitante.

    // ---- Dropzone de anexo ----
    var dropzone = document.getElementById('qr-dropzone');
    var inputAnexo = document.getElementById('qr-anexo-input');
    var labelArquivo = document.getElementById('qr-dropzone-arquivo');

    function mostrarArquivoSelecionado() {
        if (inputAnexo.files && inputAnexo.files.length > 0) {
            labelArquivo.textContent = '✓ ' + inputAnexo.files[0].name;
            labelArquivo.style.display = 'block';
        } else {
            labelArquivo.style.display = 'none';
        }
    }

    dropzone.addEventListener('click', function () {
        inputAnexo.click();
    });

    inputAnexo.addEventListener('change', mostrarArquivoSelecionado);

    ['dragenter', 'dragover'].forEach(function (evt) {
        dropzone.addEventListener(evt, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('qr-dropzone--over');
        });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        dropzone.addEventListener(evt, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('qr-dropzone--over');
        });
    });

    dropzone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            inputAnexo.files = e.dataTransfer.files;
            mostrarArquivoSelecionado();
        }
    });
})();
</script>

</body>
</html>
