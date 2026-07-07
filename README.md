# QR Service — Plugin para GLPI

> Abertura inteligente de chamados via QR Code personalizado.

## 📋 Descrição

O **QR Service** permite que clientes e usuários abram chamados no GLPI de forma rápida e intuitiva através de QR Codes personalizados. Cada QR Code gera um formulário público com identidade visual própria (cores, logo, campos customizados), sem necessidade de login.

## ✨ Funcionalidades

- 📱 **QR Codes ilimitados** — cada QR Code é independente, com token único
- 🎨 **Personalização completa** — cores, logo do cliente, título e subtítulo por QR Code
- 📝 **Campos customizados** — adicione perguntas específicas por formulário
- 🔍 **Identificação automática de usuário** — busca o solicitante por localização, telefone ou e-mail
- 📎 **Anexo de arquivos** — suporte a PNG, JPG, PDF, DOC, XLS (máx. 2MB)
- 🛡️ **Captcha matemático + honeypot** — proteção contra spam
- 📊 **Painel administrativo** — resumo de chamados, atalhos e últimos registros
- 🏢 **Logo da empresa** — personalize o painel com a identidade da sua empresa

## ⚙️ Requisitos

| Item | Versão mínima |
|------|--------------|
| GLPI | 11.0.0 |
| PHP | 8.2 |
| Composer | 2.x |
| Extensão GD | Qualquer |

## 🚀 Instalação

### 1. Clone o repositório

```bash
cd /var/www/html/glpi/plugins
git clone git@github.com:teckcomp/glpi-plugin-qrservice.git qrservice
```

### 2. Instale as dependências PHP

```bash
cd qrservice
composer install --no-dev
```

### 3. Ajuste as permissões

```bash
chown -R www-data:www-data /var/www/html/glpi/plugins/qrservice
```

### 4. Ative o plugin no GLPI

Acesse **Configurar → Plugins**, localize **QR Service** e clique em **Instalar** e depois **Ativar**.

### 5. Acesse o painel

Acesse **Administração → QR Service** ou **Configurar → QR Service**.

## 🎨 Personalização do ícone do QR Code

O ícone que aparece no canto inferior direito de cada QR Code gerado é o arquivo:
Para substituir pela logo da sua empresa:
1. Prepare uma imagem PNG com fundo transparente, tamanho recomendado **53×53px**
2. Substitua o arquivo `img/logo-plugin.png` pela sua logo
3. Todos os QR Codes gerados passarão a exibir a nova logo automaticamente

## 🏢 Logo da empresa no painel

Para exibir a logo da sua empresa no painel administrativo:
1. Acesse **Administração → QR Service**
2. Clique em **Personalizar** no bloco "Sua Marca"
3. Envie a logo (PNG ou JPG, máx. 1MB)

## 📁 Estrutura do plugin
## 🗄️ Tabelas criadas

| Tabela | Descrição |
|--------|-----------|
| `glpi_plugin_qrservice_clientes` | Clientes/Setores agrupadores |
| `glpi_plugin_qrservice_qrcodes` | QR Codes e suas configurações |
| `glpi_plugin_qrservice_campos` | Campos customizados por QR Code |
| `glpi_plugin_qrservice_chamados` | Log de chamados abertos via QR |
| `glpi_plugin_qrservice_config` | Configurações globais do plugin |

## 👤 Créditos

Desenvolvido por **Claudio Morett** — [TeckComp](https://teckcomp.com.br)

## 📄 Licença

GPL-2.0-or-later
