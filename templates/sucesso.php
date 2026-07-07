<?php
$corPrimaria = htmlspecialchars($qrcodeData['cor_primaria'] ?: '#0b1f4d');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chamado registrado</title>
<style>
    body {
        margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
        font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        background: #0a0f1f;
    }
    .box {
        background:#fff; border-radius:18px; padding:40px 30px; max-width:420px; text-align:center;
        box-shadow: 0 10px 30px rgba(0,0,0,.25);
    }
    .box h1 { color: <?php echo $corPrimaria; ?>; font-size: 22px; }
    .checkmark {
        width:64px;height:64px;border-radius:50%;
        background: <?php echo $corPrimaria; ?>; color:#fff; font-size:34px;
        display:flex;align-items:center;justify-content:center;margin:0 auto 18px;
    }
    .ticket-id { font-weight:700; }
</style>
</head>
<body>
<div class="box">
    <div class="checkmark">&#10003;</div>
    <h1>Chamado registrado com sucesso!</h1>
    <?php if (!empty($ticketID)): ?>
        <p>Seu número de chamado é <span class="ticket-id">#<?php echo (int) $ticketID; ?></span>.</p>
    <?php endif; ?>
    <p>Nossa equipe de suporte entrará em contato em breve.</p>
</div>
</body>
</html>
