<?php
include('../../../inc/includes.php');
Session::checkLoginUser();

Html::header('QR Service', '', 'config', 'GlpiPlugin\Qrservice\QrCode');

global $DB;

// --- Contadores ---
$totalClientes = countElementsInTable('glpi_plugin_qrservice_clientes');
$totalQrCodes  = countElementsInTable('glpi_plugin_qrservice_qrcodes', ['is_active' => 1]);

$hoje = date('Y-m-d');
$resHoje = $DB->doQuery("SELECT COUNT(*) as total FROM glpi_plugin_qrservice_chamados WHERE DATE(date_creation) = '$hoje'");
$totalHoje = $DB->fetchAssoc($resHoje)['total'] ?? 0;

$mes = date('Y-m');
$resMes = $DB->doQuery("SELECT COUNT(*) as total FROM glpi_plugin_qrservice_chamados WHERE DATE_FORMAT(date_creation, '%Y-%m') = '$mes'");
$totalMes = $DB->fetchAssoc($resMes)['total'] ?? 0;

$resTotal = $DB->doQuery("SELECT COUNT(*) as total FROM glpi_plugin_qrservice_chamados");
$totalGeral = $DB->fetchAssoc($resTotal)['total'] ?? 0;

// --- Logo da empresa ---
$resEmpresa = $DB->doQuery("SELECT logo_empresa FROM glpi_plugin_qrservice_config WHERE id=1");
$rowEmpresa = $DB->fetchAssoc($resEmpresa);
$logoEmpresa = $rowEmpresa['logo_empresa'] ?? '';

// --- Últimos chamados ---
$ultimosChamados = $DB->request([
    'FROM'    => 'glpi_plugin_qrservice_chamados',
    'ORDER'   => 'date_creation DESC',
    'LIMIT'   => 5,
]);

$urlClientes    = '/plugins/qrservice/front/cliente.php';
$urlQrCodes     = '/plugins/qrservice/front/qrcode.php';
$urlNovoCliente = '/plugins/qrservice/front/cliente.form.php';
$urlNovoQr      = '/plugins/qrservice/front/qrcode.form.php';

?>
<style>
.qrs-painel { font-family: -apple-system,"Segoe UI",Roboto,Arial,sans-serif; padding: 20px; max-width: 1300px; }

/* Topo */
.qrs-topo { display:flex; align-items:center; gap:20px; background:#fff; border-radius:12px; padding:24px 28px; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:24px; flex-wrap:wrap; }
.qrs-topo-logo { background:#1a3a6b; border-radius:14px; width:72px; height:72px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.qrs-topo-logo img { width:44px; filter:brightness(0) invert(1); }
.qrs-topo-info { flex:1; }
.qrs-topo-info h1 { margin:0 0 4px; font-size:26px; color:#1a3a6b; }
.qrs-topo-info p { margin:0 0 8px; color:#666; font-size:14px; }
.qrs-badge { display:inline-block; background:#e8f0fe; color:#1a3a6b; font-size:12px; font-weight:700; padding:3px 10px; border-radius:20px; }
.qrs-topo-marca { background:#f9fafb; border:2px dashed #d0d5dd; border-radius:10px; width:160px; text-align:center; padding:16px 10px; }
.qrs-topo-marca img { max-height:48px; max-width:120px; object-fit:contain; display:block; margin:0 auto 8px; }
.qrs-topo-marca span { font-size:11px; color:#888; display:block; margin-bottom:8px; }
.qrs-topo-status { text-align:right; min-width:160px; }
.qrs-topo-status .qrs-status-title { font-size:13px; font-weight:700; color:#333; margin-bottom:6px; }
.qrs-status-ativo { display:flex; align-items:center; gap:6px; color:#16a34a; font-weight:700; font-size:15px; }
.qrs-status-ativo::before { content:''; width:10px; height:10px; background:#16a34a; border-radius:50%; display:inline-block; }
.qrs-status-sub { font-size:12px; color:#888; margin-top:2px; }

/* Seção */
.qrs-section-title { font-size:16px; font-weight:700; color:#333; margin:0 0 14px; }

/* Cards de navegação */
.qrs-cards { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:24px; }
.qrs-card { background:#fff; border-radius:12px; padding:24px 20px; box-shadow:0 2px 8px rgba(0,0,0,.08); text-align:center; display:flex; flex-direction:column; align-items:center; }
.qrs-card p { flex:1; margin:0 0 16px; }
.qrs-card p { flex:1; }
.qrs-card-icon { font-size:36px; margin-bottom:12px; }
.qrs-card h3 { margin:0 0 8px; font-size:16px; color:#1a1a2e; }
.qrs-card p { margin:0 0 16px; font-size:13px; color:#666; line-height:1.4; }
.qrs-card-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:8px; font-weight:700; font-size:14px; text-decoration:none; color:#fff; transition:opacity .15s; }
.qrs-card-btn:hover { opacity:.85; color:#fff; }
.btn-blue   { background:#1a3a6b; }
.btn-green  { background:#16a34a; }
.btn-purple { background:#7c3aed; }
.btn-orange { background:#ea580c; }
.btn-teal   { background:#0d9488; }
.btn-gray   { background:#9ca3af; cursor:not-allowed; }

/* Resumo */
.qrs-resumo { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:24px; }
.qrs-resumo-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.08); display:flex; align-items:center; gap:14px; }
.qrs-resumo-icon { font-size:28px; }
.qrs-resumo-num { font-size:28px; font-weight:800; color:#1a1a2e; line-height:1; }
.qrs-resumo-label { font-size:12px; color:#666; margin-top:2px; }
.qrs-resumo-sub { font-size:11px; color:#aaa; }

/* Inferior */
.qrs-bottom { display:grid; grid-template-columns:1fr; gap:20px; margin-bottom:24px; }
@media(max-width:700px){ .qrs-bottom { grid-template-columns:1fr; } }
.qrs-box { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.qrs-atalho { display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid #f1f3f5; text-decoration:none; color:#1a3a6b; font-size:14px; font-weight:600; }
.qrs-atalho:last-child { border-bottom:none; }
.qrs-atalho:hover { color:#16a34a; }
.qrs-atalho-left { display:flex; align-items:center; gap:8px; }

/* Chamados */
.qrs-chamados-vazio { text-align:center; padding:30px; color:#aaa; }
.qrs-chamados-vazio .qrs-vazio-icon { font-size:40px; margin-bottom:8px; }
.qrs-chamado-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f1f3f5; font-size:13px; }
.qrs-chamado-row:last-child { border-bottom:none; }
.qrs-ver-todos { display:block; text-align:center; margin-top:14px; padding:8px; border:1px solid #d0d5dd; border-radius:8px; color:#555; font-size:13px; text-decoration:none; }
.qrs-ver-todos:hover { background:#f9fafb; }

/* Rodapé */
.qrs-footer { text-align:center; color:#aaa; font-size:12px; padding-top:10px; border-top:1px solid #f1f3f5; }

.qrs-sidebar { background:#f9fafb; border-radius:12px; padding:20px; }
.qrs-sidebar h4 { margin:0 0 8px; font-size:14px; color:#1a3a6b; }
.qrs-sidebar p { margin:0 0 12px; font-size:13px; color:#666; line-height:1.5; }
.qrs-feature { display:flex; align-items:center; gap:8px; font-size:13px; color:#444; margin-bottom:6px; }
.qrs-ajuda { background:#f0fdf4; border-radius:8px; padding:14px; margin-top:16px; }
.qrs-ajuda h5 { margin:0 0 4px; color:#16a34a; font-size:13px; }
.qrs-ajuda p { margin:0; font-size:12px; color:#555; }
</style>

<div class="qrs-painel">

    <!-- TOPO -->
    <div class="qrs-topo">
        <div class="qrs-topo-logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="44" height="44">
  <rect width="100" height="100" rx="18" fill="none"/>
  <rect x="12" y="12" width="30" height="30" rx="4" fill="none" stroke="#fff" stroke-width="5"/>
  <rect x="18" y="18" width="18" height="18" rx="2" fill="#4fc3f7"/>
  <rect x="58" y="12" width="30" height="30" rx="4" fill="none" stroke="#fff" stroke-width="5"/>
  <rect x="64" y="18" width="18" height="18" rx="2" fill="#4fc3f7"/>
  <rect x="12" y="58" width="30" height="30" rx="4" fill="none" stroke="#fff" stroke-width="5"/>
  <rect x="18" y="64" width="18" height="18" rx="2" fill="#4fc3f7"/>
  <rect x="48" y="48" width="8" height="8" rx="1" fill="#fff"/>
  <rect x="60" y="48" width="8" height="8" rx="1" fill="#fff"/>
  <rect x="72" y="48" width="8" height="8" rx="1" fill="#4fc3f7"/>
  <rect x="48" y="60" width="8" height="8" rx="1" fill="#4fc3f7"/>
  <rect x="60" y="60" width="8" height="8" rx="1" fill="#fff"/>
  <rect x="72" y="60" width="8" height="8" rx="1" fill="#fff"/>
  <rect x="48" y="72" width="8" height="8" rx="1" fill="#fff"/>
  <rect x="60" y="72" width="8" height="8" rx="1" fill="#4fc3f7"/>
  <rect x="72" y="72" width="8" height="8" rx="1" fill="#fff"/>
</svg>
        </div>
        <div class="qrs-topo-info">
            <h1>QR Service</h1>
            <p>Abertura Inteligente de Chamados</p>
            <span class="qrs-badge">Versão: 0.1.0-alpha</span>
        </div>
        <div style="flex:1"></div>
        <div class="qrs-topo-marca">
                        <?php if (!empty($logoEmpresa)): ?>
                <img id="qrs-empresa-preview"
                     src="/plugins/qrservice/ajax/config-logo.php?v=<?php echo time(); ?>"
                     style="max-height:48px;max-width:120px;object-fit:contain;display:block;margin:4px auto;">
            <?php else: ?>
                <span>Sua Marca</span>
            <?php endif; ?>
            <button onclick="document.getElementById('qrs-modal-logo').style.display='flex'" style="font-size:12px;background:#1a3a6b;color:#fff;padding:4px 12px;border-radius:6px;border:none;cursor:pointer;margin-top:4px;">Personalizar</button>
        </div>
        <div class="qrs-topo-status">
            <div class="qrs-status-title">Status do Plugin</div>
            <div class="qrs-status-ativo">Ativo</div>
            <div class="qrs-status-sub">Plugin funcionando normalmente</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:220px 1fr;gap:20px;">

        <!-- SIDEBAR -->
        <div>
            <div class="qrs-sidebar">
                <h4>O que é o QR Service?</h4>
                <p>Permite que seus clientes ou usuários abram chamados de forma rápida e inteligente através de QR Codes personalizados.</p>
                <div class="qrs-feature">📋 QR Codes ilimitados</div>
                <div class="qrs-feature">🎨 Personalização completa</div>
                <div class="qrs-feature">📝 Campos personalizados</div>
                <div class="qrs-feature">🔗 Integração total com GLPI</div>
                <div class="qrs-feature">📊 Relatórios e métricas</div>
                <div class="qrs-ajuda">
                    <h5>Precisa de ajuda?</h5>
                    <p>Consulte a <a href="https://github.com/teckcomp/glpi-plugin-qrservice" target="_blank">documentação</a> ou fale com o administrador.</p>
                </div>
            </div>
        </div>

        <!-- CONTEÚDO PRINCIPAL -->
        <div>

            <!-- CARDS DE NAVEGAÇÃO -->
            <p class="qrs-section-title">Painel Administrativo</p>
            <div class="qrs-cards">
                <div class="qrs-card">
                    <div class="qrs-card-icon">👥</div>
                    <h3>Clientes / Setores</h3>
                    <p>Gerencie clientes, setores e responsáveis</p>
                    <a href="<?php echo $urlClientes; ?>" class="qrs-card-btn btn-blue">Gerenciar →</a>
                </div>
                <div class="qrs-card">
                    <div class="qrs-card-icon">📱</div>
                    <h3>QR Codes</h3>
                    <p>Crie e gerencie QR Codes personalizados</p>
                    <a href="<?php echo $urlQrCodes; ?>" class="qrs-card-btn btn-green">Gerenciar →</a>
                </div>
            </div>

            <!-- RESUMO GERAL -->
            <p class="qrs-section-title">Resumo Geral</p>
            <div class="qrs-resumo">
                <div class="qrs-resumo-card">
                    <div class="qrs-resumo-icon">👥</div>
                    <div>
                        <div class="qrs-resumo-num"><?php echo $totalClientes; ?></div>
                        <div class="qrs-resumo-label">Clientes / Setores</div>
                        <div class="qrs-resumo-sub">Cadastrados</div>
                    </div>
                </div>
                <div class="qrs-resumo-card">
                    <div class="qrs-resumo-icon">📱</div>
                    <div>
                        <div class="qrs-resumo-num"><?php echo $totalQrCodes; ?></div>
                        <div class="qrs-resumo-label">QR Codes</div>
                        <div class="qrs-resumo-sub">Ativos</div>
                    </div>
                </div>
                <div class="qrs-resumo-card">
                    <div class="qrs-resumo-icon">📨</div>
                    <div>
                        <div class="qrs-resumo-num"><?php echo $totalHoje; ?></div>
                        <div class="qrs-resumo-label">Chamados Hoje</div>
                        <div class="qrs-resumo-sub">Abertos via QR</div>
                    </div>
                </div>
                <div class="qrs-resumo-card">
                    <div class="qrs-resumo-icon">📈</div>
                    <div>
                        <div class="qrs-resumo-num"><?php echo $totalMes; ?></div>
                        <div class="qrs-resumo-label">Chamados Este Mês</div>
                        <div class="qrs-resumo-sub">Total via QR</div>
                    </div>
                </div>
                <div class="qrs-resumo-card">
                    <div class="qrs-resumo-icon">🕐</div>
                    <div>
                        <div class="qrs-resumo-num"><?php echo $totalGeral; ?></div>
                        <div class="qrs-resumo-label">Total de Chamados</div>
                        <div class="qrs-resumo-sub">Desde o início</div>
                    </div>
                </div>
            </div>

            <!-- ATALHOS + ÚLTIMOS CHAMADOS -->
            <div class="qrs-bottom">
                <div class="qrs-box">
                    <p class="qrs-section-title">Últimos Chamados via QR Code</p>
                    <?php if (iterator_count($ultimosChamados) === 0): ?>
                        <div class="qrs-chamados-vazio">
                            <div class="qrs-vazio-icon">📭</div>
                            <div>Nenhum chamado encontrado</div>
                            <small>Os chamados abertos via QR Code aparecerão aqui.</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ultimosChamados as $c): ?>
                            <div class="qrs-chamado-row">
                                <span>#<?php echo $c['tickets_id']; ?> — <?php echo htmlspecialchars($c['nome_solicitante'] ?? 'Solicitante'); ?></span>
                                <span style="color:#aaa;font-size:11px;"><?php echo date('d/m H:i', strtotime($c['date_creation'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <a href="/front/ticket.php?criteria[0][field]=9&criteria[0][searchtype]=equals&criteria[0][value]=<?php $rtQr = new RequestType(); echo $rtQr->getFromDBByCrit(['name' => 'QR Code']) ? $rtQr->getID() : 0; ?>&reset=reset" class="qrs-ver-todos">Ver todos os chamados</a>
                </div>
            </div>

        </div>
    </div>

    <div class="qrs-footer">
        QR Service - Plugin para GLPI | Desenvolvimento: Claudio Morett | Versão 0.1.0-alpha
    </div>

</div>

<!-- MODAL LOGO EMPRESA -->
<div id="qrs-modal-logo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:380px;max-width:95vw;box-shadow:0 8px 32px rgba(0,0,0,.2);">
        <h3 style="margin:0 0 16px;color:#1a3a6b;">Logo da sua empresa</h3>
        <p style="font-size:13px;color:#666;margin:0 0 16px;">PNG ou JPG, máx. 1MB. Aparece no painel administrativo.</p>
        <form method="POST" enctype="multipart/form-data" action="/plugins/qrservice/ajax/config-logo.php" id="qrs-logo-form">
        <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>

        <div id="qrs-modal-preview-wrap" style="text-align:center;margin-bottom:16px;">
            <?php if (!empty($logoEmpresa)): ?>
                <img id="qrs-modal-preview"
                     src="/plugins/qrservice/ajax/config-logo.php?v=<?php echo time(); ?>"
                     style="max-height:80px;max-width:200px;object-fit:contain;border:1px solid #eee;border-radius:8px;padding:8px;">
            <?php else: ?>
                <img id="qrs-modal-preview" src="" style="display:none;max-height:80px;max-width:200px;object-fit:contain;border:1px solid #eee;border-radius:8px;padding:8px;">
            <?php endif; ?>
        </div>

        <input type="file" id="qrs-modal-file" name="logo_empresa" accept="image/png,image/jpeg"
               style="display:block;width:100%;margin-bottom:16px;">

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="document.getElementById('qrs-modal-logo').style.display='none'"
                    style="padding:8px 18px;border:1px solid #ccc;border-radius:8px;background:#fff;cursor:pointer;">Cancelar</button>
            <button type="submit"
                    style="padding:8px 18px;border:none;border-radius:8px;background:#1a3a6b;color:#fff;font-weight:700;cursor:pointer;">Salvar</button>
        </div>
        <div id="qrs-modal-msg" style="margin-top:10px;font-size:13px;text-align:center;"></div>
        </form>
    </div>
</div>

<script>
document.getElementById('qrs-modal-file').addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    var preview = document.getElementById('qrs-modal-preview');
    var reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
});

</script>

<?php Html::footer(); ?>
