# QR Service — Plugin para GLPI

> Abertura inteligente de chamados via QR Code personalizado.

## 📋 Descrição

O **QR Service** permite que clientes e visitantes abram chamados no GLPI de forma rápida e intuitiva através de QR Codes personalizados. Cada QR Code gera um formulário público com identidade visual própria (cores, logo, campos customizados), sem necessidade de login.

## ✨ Funcionalidades

- 📱 **QR Codes ilimitados** — cada QR Code é independente, com token único
- 🏬 **Múltiplas Marcas por Cliente** — vincule várias árvores de localização a um mesmo cliente
- 🗺️ **Cascata adaptativa de localização** — Marca → Unidade → Localização, com colapso automático de níveis sem filhos
- 🎨 **Personalização completa** — cores em gradiente, logo do cliente, título e subtítulo por QR Code
- 📝 **Campos customizados** — adicione perguntas específicas por formulário
- 🔍 **Identificação automática do solicitante** — em camadas: usuário vinculado à Localização, à Unidade ou à Marca; fallback por telefone/e-mail
- 🏷️ **Origem dedicada "QR Code"** — chamados nascem com origem própria, fácil de filtrar e reportar
- 📎 **Anexo de arquivos** — suporte a PNG, JPG, PDF, DOC, XLS (máx. 2MB)
- 🛡️ **Captcha matemático + honeypot** — proteção contra spam
- 📊 **Painel administrativo** — resumo de chamados via QR e acesso rápido aos cadastros

## ⚙️ Requisitos

| Item | Versão mínima |
|------|--------------|
| GLPI | 11.0.0 |
| PHP | 8.2 |
| Extensão GD | Qualquer |

## 🚀 Instalação (recomendada — via Release)

O pacote da release já inclui todas as dependências (não precisa de Composer).

```bash
cd /caminho/do/glpi/plugins
wget https://github.com/teckcomp/glpi-plugin-qrservice/releases/download/v0.1.1-alpha/qrservice-v0.1.1-alpha.tar.gz
tar -xzf qrservice-v0.1.1-alpha.tar.gz && rm qrservice-v0.1.1-alpha.tar.gz
chown -R www-data:www-data qrservice
```

Depois, no GLPI: **Configurar → Plugins → QR Service → Instalar → Ativar** (tabelas, direitos e a origem "QR Code" são criados automaticamente). Faça logout/login para carregar os novos direitos.

### Instalação para desenvolvimento (via git)

```bash
cd /caminho/do/glpi/plugins
git clone https://github.com/teckcomp/glpi-plugin-qrservice.git qrservice
cd qrservice && composer install --no-dev
chown -R www-data:www-data /caminho/do/glpi/plugins/qrservice
```

## 🏁 Primeiros passos

1. Crie um **usuário de serviço** (ex.: `forms.qrcode`) com perfil **Self-Service**, entidade raiz, ativo e **sem e-mail** — ele será o "Usuário técnico padrão" dos QR Codes
2. Garanta a árvore de **Localizações**: a Marca é uma localização de topo; abaixo dela, Unidades e Localizações (o formulário usa até 3 níveis)
3. Cadastre um **Cliente** em Administração → QR Service e vincule as Marcas dele
4. Crie um **QR Code** para o cliente, defina o usuário técnico padrão e a entidade de destino dos chamados
5. Personalize cores/logo, baixe a imagem do QR e imprima

## 🏢 Logo da empresa no painel

1. Acesse **Administração → QR Service**
2. Clique em **Personalizar** no cabeçalho do painel
3. Envie a logo (PNG ou JPG, máx. 1MB)

## 🗄️ Tabelas criadas

| Tabela | Descrição |
|--------|-----------|
| `glpi_plugin_qrservice_clientes` | Clientes agrupadores |
| `glpi_plugin_qrservice_clientes_marcas` | Vínculo N-N Cliente ↔ Marcas (localizações de topo) |
| `glpi_plugin_qrservice_qrcodes` | QR Codes e suas configurações |
| `glpi_plugin_qrservice_campos` | Campos customizados por QR Code |
| `glpi_plugin_qrservice_chamados` | Log de chamados abertos via QR |
| `glpi_plugin_qrservice_config` | Configurações globais do plugin |

A desinstalação remove as tabelas e os direitos; a origem "QR Code" é mantida de propósito (chamados antigos continuam classificados).

## 🗺️ Roadmap

Melhorias planejadas e conhecidas estão registradas nas [Issues](https://github.com/teckcomp/glpi-plugin-qrservice/issues).

## 👤 Créditos

Desenvolvido por **Claudio Morett** — [TeckComp](https://teckcomp.com.br)

## 📄 Licença

[GPL-2.0-or-later](LICENSE)
