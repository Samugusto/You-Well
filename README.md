<div align="center">
  <img src="https://youwell.netlify.app/img/Sem%20t%C3%ADtulo.png" height="120px">
  <h1>YouWell</h1>
  <h3>Sub-empresa da <a href="https://devsbee.com.br/">DataBee</a> </h3>
</div>

  <h1>Sobre a Youwell</h1>
<h3>Youwell é uma plataforma digital (WEB) para o monitoramento de setores empresariais em industriais para a garantia da segurança dos colaboradores usando como base sensores de temperatura, ruído e qualidade do ar, tendo informações essenciais para a empresa administrar cada setor.</h3>

<h1>Equipe:</h1>

| Nome  | Funções |
| ------------- | ------------- |
| Gabriela de Moura  | FullStack / Project owner  |
| Diego Tavares  | Criação e análise de banco de dados  |
| Amanda Monteleone  | Documentação e testes  |
| Yago Pavan  | Back-End  |
| Samuel Barbosa  | Front-End  |
<br>
  
  <h2>Estado atual do projeto:</h2>

  <h3>Ainda em desenvolvimento.</h3><br>
  <ul>
  <li>Front-end: Em andamento</li>
  <li>Back-end: Em andamento</li>
  <li>Banco de dados: Completo</li>
  </ul>
  <div align="center">
  <b>principais tecnologias utilizadas no projeto:</b><br>
  <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/99/Unofficial_JavaScript_logo_2.svg/960px-Unofficial_JavaScript_logo_2.svg.png" width="3%">
  <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/HTML5_Badge.svg/1280px-HTML5_Badge.svg.png" width="3%">
  <img src="https://thumb.wikimedia.org/wikipedia/commons/thumb/d/d5/CSS3_logo_and_wordmark.svg/500px-CSS3_logo_and_wordmark.svg.png?utm_source=pt.wikipedia.org&utm_campaign=index&utm_content=thumbnail" width="2.5%">
  <img src="https://cdn-icons-png.flaticon.com/512/5968/5968313.png" width="3%">
  <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/PHP-logo.svg/1280px-PHP-logo.svg.png" width=3%>
    <h4> 2026 YouWell © - Saúde e bem estar corporativo</h4>
</div>

## Publicação no cPanel

Envie esta pasta com o nome `You-Well` para `public_html/You-Well` (não crie um nível extra dentro dela). Com a DataBee diretamente em `public_html`, os endereços serão `https://devsbee.com.br/` e `https://devsbee.com.br/You-Well/`.

O `.htaccess` dentro de `You-Well` define URLs amigáveis: `/You-Well/` (início), `/You-Well/codigo/`, `/You-Well/entrar/`, `/You-Well/cadastro/`, `/You-Well/dashboard/` e `/You-Well/admin/`. As seções do painel usam `/You-Well/dashboard/nome-da-secao/`. Essas rotas exigem Apache com `mod_rewrite` habilitado; envie o `.htaccess` que está dentro de `You-Well`.

Os fluxos de cadastro, login e painel só funcionam depois de configurar PHP, banco de dados e serviço de e-mail conforme indicado acima.

Antes de habilitar o sistema:

1. Crie um banco MySQL pelo cPanel e importe nele o esquema do projeto.
2. Copie `config/database.local.php.example` para `config/database.local.php` e preencha os dados do banco fornecidos pelo cPanel.
3. Preencha `api_key` e `from` em `config/resend.local.php` com os dados do serviço de e-mail.
4. Mantenha `config/.htaccess` e a raiz `.htaccess` publicados; eles bloqueiam o acesso HTTP à configuração e desativam a listagem de diretórios.

Não há um dump SQL do banco nesta pasta; o sistema depende do esquema e dos dados existentes do projeto. Os nomes locais dos arquivos de imagem e da página de ativação são minúsculos e sem acentos/espaços para evitar diferenças de maiúsculas e codificação no Linux. Não envie arquivos de credenciais antigos; use somente credenciais próprias do cPanel e uma nova chave de e-mail.
