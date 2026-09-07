# Biblioteca de mídia

A biblioteca de mídia (**Administração → Mídia**) centraliza o upload e a
organização de imagens, com otimização automática quando disponível.

## Upload

- Suporta upload **múltiplo** (`files[]`) e único.
- Validação por MIME real, tamanho e verificação de imagem (ver
  [security.md](security.md)).
- Nomes de arquivo são aleatórios; o nome original é preservado apenas como
  metadado exibível.

## Variantes e WebP

Ao salvar, o `MediaService` gera variantes redimensionadas quando a extensão
**GD** está presente:

| Variante    | Largura máxima |
| ----------- | -------------- |
| `thumbnail` | 300px          |
| `medium`    | 800px          |
| `large`     | 1600px         |

Regras:

- Prefere **WebP** quando o GD suporta; caso contrário mantém o formato original.
- Preserva transparência (PNG/WebP).
- Não amplia imagens menores que o alvo.
- As variantes ficam registradas em `media.variants` (JSON).

**Degradação graciosa**: sem GD, o upload funciona e apenas o arquivo original é
armazenado (sem variantes). O painel de **Diagnóstico** indica se GD/WebP estão
disponíveis.

## Uso das variantes

`Media::variant($media, $size)` devolve o caminho da variante desejada (ou o
original como fallback). Use a menor variante adequada ao contexto para reduzir
o peso da página.

## Metadados

Cada item guarda `title` e `alt_text`. Preencha o **texto alternativo** para
acessibilidade e SEO das imagens.

## Exclusão

Excluir um item remove o registro e os arquivos físicos (original + variantes).
