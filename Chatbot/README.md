# Chatbot com Gemini

## Configuração PHP (sem Node.js)

1. Copie `gemini.local.php.example` para `gemini.local.php` e configure sua chave da API Gemini. Esse arquivo é bloqueado para acesso direto pelo Apache.
2. Crie `database.local.php` com base em `database.local.php.example`, preenchendo os dados do MySQL. A conexão é usada diretamente pelo PHP para buscar dados operacionais.
3. Publique os arquivos PHP, `.htaccess` e imagens do chatbot no cPanel. Habilite a extensão PHP cURL e conexões HTTPS de saída para `generativelanguage.googleapis.com`.
4. A página do chatbot fica em `/dashboard/chatbot/` e só pode ser acessada por administradores.

A chave é usada somente pelo servidor e não é enviada ao navegador. Também é possível definir `GEMINI_API_KEY`, `GEMINI_MODEL` e `GEMINI_FALLBACK_MODEL` como variáveis de ambiente do PHP. Se o modelo principal retornar erro 429 ou 503, o endpoint tenta o modelo alternativo.

As respostas são exibidas após o Gemini concluir a geração, não em streaming. O endpoint não registra o conteúdo das conversas.

O endpoint consulta somente as tabelas `ruido`, `temperatura`, `qualidade_ar`, `dispositivos` e `setores`, nunca `usuarios`, e retorna no máximo cinco registros por tabela. A consulta SQL é definida no endpoint; o chatbot não pode enviar SQL arbitrário.

Os arquivos legados `server.js`, `index.html` e `style.css` foram preservados porque têm alterações locais não commitadas. O painel PHP não os utiliza; a interface atual está em `controllers/painel.php` e `public/assets/css/styleP.css`.

## Personalidade do assistente

A personalidade é enviada pelo próprio endpoint PHP como instrução de sistema. Para ajustar o tom, edite `$systemInstruction` em `chatbot-api.php`.
