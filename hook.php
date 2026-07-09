<?php

/**
 * -------------------------------------------------------------------------
 * Instalação: cria as tabelas do plugin
 * -------------------------------------------------------------------------
 */
function plugin_qrservice_install()
{
    global $DB;

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

    $migration = new Migration(PLUGIN_QRSERVICE_VERSION);

    // ---------------------------------------------------------------
    // Tabela: glpi_plugin_qrservice_clientes
    // (Agrupador organizacional: uma empresa/cliente pode ter vários QR Codes)
    // ---------------------------------------------------------------
    $table = 'glpi_plugin_qrservice_clientes';
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `entities_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            `name` varchar(255) NOT NULL DEFAULT '',
            `is_active` tinyint NOT NULL DEFAULT 1,
            `locations_id_raiz` int {$default_key_sign} NOT NULL DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
          COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
    }
    if (!$DB->fieldExists($table, 'locations_id_raiz')) {
        $DB->doQuery("ALTER TABLE `$table` ADD COLUMN `locations_id_raiz` int {$default_key_sign} NOT NULL DEFAULT 0 AFTER `is_active`") or die($DB->error());
    }

    // ---------------------------------------------------------------
    // Tabela: glpi_plugin_qrservice_clientes_marcas (N-N Cliente <-> Marcas)
    // ---------------------------------------------------------------
    $table = 'glpi_plugin_qrservice_clientes_marcas';
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `plugin_qrservice_clientes_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            `locations_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `cliente_location` (`plugin_qrservice_clientes_id`, `locations_id`),
            KEY `locations_id` (`locations_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
          COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
        // Migra o vínculo antigo (locations_id_raiz) para a nova tabela
        $DB->doQuery("INSERT IGNORE INTO `$table` (plugin_qrservice_clientes_id, locations_id)
            SELECT id, locations_id_raiz FROM glpi_plugin_qrservice_clientes WHERE locations_id_raiz > 0");
    }


    // ---------------------------------------------------------------
    // Tabela: glpi_plugin_qrservice_qrcodes
    // (Cada QR Code é independente: token, cores, logo e campos próprios)
    // ---------------------------------------------------------------
    $table = 'glpi_plugin_qrservice_qrcodes';
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `plugin_qrservice_clientes_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            `entities_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            `name` varchar(255) NOT NULL DEFAULT '',
            `unidade` varchar(255) NOT NULL DEFAULT '',
            `token` varchar(64) NOT NULL DEFAULT '',
            `cor_primaria` varchar(20) NOT NULL DEFAULT '#0b1f4d',
            `cor_secundaria` varchar(20) NOT NULL DEFAULT '#1a2f6b',
            `logo_path` varchar(255) NOT NULL DEFAULT '',
            `titulo_formulario` varchar(255) NOT NULL DEFAULT 'Abertura de Chamado',
            `subtitulo_formulario` varchar(255) NOT NULL DEFAULT 'Suporte Técnico',
            `users_id_default_requester` int {$default_key_sign} NOT NULL DEFAULT 0,
            `entities_id_ticket` int {$default_key_sign} NOT NULL DEFAULT 0,
            `is_active` tinyint NOT NULL DEFAULT 1,
            `modo_localizacao` tinyint NOT NULL DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token` (`token`),
            KEY `plugin_qrservice_clientes_id` (`plugin_qrservice_clientes_id`),
            KEY `entities_id` (`entities_id`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
          COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
    }
    if (!$DB->fieldExists($table, 'modo_localizacao')) {
        $DB->doQuery("ALTER TABLE `$table` ADD COLUMN `modo_localizacao` tinyint NOT NULL DEFAULT 0") or die($DB->error());
    }


    // ---------------------------------------------------------------
    // Tabela: glpi_plugin_qrservice_campos
    // (Perguntas do formulário, configuráveis por QR Code — 3, 4, 10, 12...)
    // ---------------------------------------------------------------
    $table = 'glpi_plugin_qrservice_campos';
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `plugin_qrservice_qrcodes_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            `label` varchar(255) NOT NULL DEFAULT '',
            `tipo` varchar(50) NOT NULL DEFAULT 'text',
            `obrigatorio` tinyint NOT NULL DEFAULT 0,
            `ordem` int NOT NULL DEFAULT 0,
            `ativo` tinyint NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `plugin_qrservice_qrcodes_id` (`plugin_qrservice_qrcodes_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
          COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
    }

    // ---------------------------------------------------------------
    // Tabela: glpi_plugin_qrservice_chamados
    // (Log de chamados abertos via QR Code, para os relatórios)
    // ---------------------------------------------------------------
    $table = 'glpi_plugin_qrservice_chamados';
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `plugin_qrservice_qrcodes_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            `tickets_id` int {$default_key_sign} NOT NULL DEFAULT 0,
            `nome_solicitante` varchar(255) NOT NULL DEFAULT '',
            `telefone_solicitante` varchar(50) NOT NULL DEFAULT '',
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_qrservice_qrcodes_id` (`plugin_qrservice_qrcodes_id`),
            KEY `tickets_id` (`tickets_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
          COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
    }

    // ---------------------------------------------------------------
    // Tabela: glpi_plugin_qrservice_config
    // (Configurações globais do plugin: logo da empresa instaladora)
    // ---------------------------------------------------------------
    $table = 'glpi_plugin_qrservice_config';
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `logo_empresa` varchar(255) NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
          COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query) or die($DB->error());
        // Insere registro padrão
        $DB->doQuery("INSERT INTO `$table` (id) VALUES (1)");
    }

    // ---------------------------------------------------------------
    // Direitos do plugin (glpi_profilerights)
    // ---------------------------------------------------------------
    ProfileRight::addProfileRights(['plugin_qrservice']);
    $DB->updateOrInsert('glpi_profilerights', ['rights' => 255], [
        'profiles_id' => 4,
        'name'        => 'plugin_qrservice',
    ]);

    // ---------------------------------------------------------------
    // Origem da requisição "QR Code" (usada pelo formulário público)
    // ---------------------------------------------------------------
    $rtQr = new RequestType();
    if (!$rtQr->getFromDBByCrit(['name' => 'QR Code'])) {
        $rtQr->add([
            'name'            => 'QR Code',
            'is_active'       => 1,
            'is_ticketheader' => 1,
            'is_itilfollowup' => 1,
        ]);
    }

    $migration->executeMigration();

    return true;
}

/**
 * -------------------------------------------------------------------------
 * Desinstalação: remove as tabelas do plugin
 * -------------------------------------------------------------------------
 */
function plugin_qrservice_uninstall()
{
    global $DB;

    $tables = [
        'glpi_plugin_qrservice_clientes_marcas',
        'glpi_plugin_qrservice_chamados',
        'glpi_plugin_qrservice_campos',
        'glpi_plugin_qrservice_qrcodes',
        'glpi_plugin_qrservice_clientes',
        'glpi_plugin_qrservice_config',
    ];

    foreach ($tables as $table) {
        $DB->doQuery("DROP TABLE IF EXISTS `$table`");
    }

    // Remove os direitos do plugin
    $DB->delete('glpi_profilerights', ['name' => 'plugin_qrservice']);

    return true;
}
