# Site Nozomu

Site do Restaurante Nozomu — São Bernardo do Campo.

## Estrutura

- `index.html` — home (capa em slide, a casa, rodízio, espaços, avaliações, como chegar)
- `menu.html` — pratos por categoria
- `ambiente.html` — espaços da casa e galeria
- `reservas.html` — sistema de reserva + painel da equipe
- `assets/` — CSS, JS, fotos, avaliações

## Publicar

Suba todos os arquivos na raiz do repositório e ative GitHub Pages
(Settings → Pages → branch `main`, pasta `/root`).

## Atualizar as avaliações

Edite apenas `assets/avaliacoes.json`:

```json
{
  "resumo": { "nota": "4,8", "total": "312" },
  "linkGoogle": "https://g.page/r/SEU-LINK",
  "avaliacoes": [
    { "nome": "Ana Paula", "nota": 5, "quando": "há 2 semanas", "texto": "Peixe fresquíssimo." }
  ]
}
```

Com a lista vazia, a seção mostra apenas o convite e os botões para o Google.

## Observação

`reservas.html` é gerado a partir de `Reservas Nozomu.html` (arquivo-fonte).
Para alterar o fluxo de reserva, edite o fonte e recompile.
