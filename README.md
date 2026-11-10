# Checklist de máquinas com rastreamento

Sistema em Laravel para empresas que locam ou operam máquinas: o operador faz o checklist pelo celular (acesso por QR code), o painel mostra alertas, e a localização e as horas de trabalho vêm de rastreadores GPS.

> **Este repositório é uma amostra de código-fonte**, organizada na estrutura de pastas do Laravel. Não inclui o esqueleto do framework (`composer.json`, `config/`, `public/`), por isso não roda sozinha. Serve para leitura da lógica: controladores, modelos, serviços, migrações e telas.

## O que tem

- Checklists por modelo de formulário e por máquina, com alertas de problema e histórico.
- Rastreamento: webhook do flespi (`app/Http/Controllers/Api/FlespiWebhookController.php`) que grava posições e eventos por IMEI.
- Horímetro por ignição (`app/Services/HorimetroService.php`): soma as sessões com ignição ligada; considera online quem enviou posição nos últimos 3 minutos.
- Endereço da posição (`ReverseGeocodingService`): reaproveita endereço próximo (80 m) e guarda consultas em cache por 30 dias.
- Controle de vencimento da ART de segurança, locação, orçamento e contrato em PDF.
- Perfis de acesso; o perfil comercial não entra nas áreas administrativas (`BlockCommercialAccess`).

## Estrutura

```
app/Http/Controllers/   Admin, Operador, Auth, Api (webhook)
app/Http/Middleware/    controle de perfil e módulos
app/Models/             máquinas, operadores, formulários, rastreadores
app/Services/           horímetro, geocodificação, PDFs
database/migrations/    esquema versionado
resources/views/        telas Blade
routes/web.php          rotas
```

## Webhook do flespi

Aceita somente `Authorization: Bearer <FLESPI_WEBHOOK_TOKEN>`, compara com `hash_equals` e recusa lotes com mais de 500 mensagens. Detalhes do fluxo estão em `docs/tecnico/rastreamento-imei-flespi.md` do repositório de portfólio.

Limites conhecidos: o dispositivo desconhecido é criado com status `aguardando_vinculo` (sem fila de aprovação), não há idempotência de posições e o token vem de `env()` (com cache de configuração, deve passar por um arquivo `config/`).
