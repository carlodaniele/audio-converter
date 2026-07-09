# Audio Converter for WP

Plugin WordPress standalone per generare una bozza di articolo a partire da un file audio, usando AI Connectors nativi di WordPress 7.x.

## Cosa fa

- Registra ability core: `audio-converter-for-wp/audio-to-post`
- Espone endpoint run via Abilities API core: `POST /wp-json/wp-abilities/v1/audio-converter-for-wp/audio-to-post/run`
- Aggiunge pagina impostazioni in `Settings > Audio Converter`
- Aggiunge sidebar Gutenberg `Audio Converter`
- Permette selezione audio dalla Media Library (filtro tipo `audio`)
- Esegue pipeline AI in due passaggi:
  - trascrizione audio
  - strutturazione JSON del contenuto
- Normalizza il payload e crea blocchi Gutenberg (heading/paragraph/list)
- Crea un post in bozza (`draft`)
- Applica idempotenza su `external_run_id`

## Requisiti

- WordPress 7.0 o superiore
- PHP 8.0 o superiore
- AI Connectors configurati su WordPress
- Utente con capability `edit_posts`

## Installazione

1. Copia la cartella `audio-converter-for-wp` dentro `wp-content/plugins/`
2. Vai su `Plugin > Installed Plugins`
3. Attiva `Audio Converter for WP`
4. Vai su `Settings > Audio Converter` e salva i default editoriali

## Configurazione (wp-admin)

Pagina: `Settings > Audio Converter`

Campi disponibili:
- Default language (es. `en-US`, `it-IT`)
- Default tone (`professional`, `neutral`, `conversational`)
- Default target length (`short`, `medium`, `long`)

Questi valori vengono usati come default nella sidebar Gutenberg.

## Uso nel Block Editor (Gutenberg)

1. Apri o crea un post
2. Apri il menu del Block Editor e seleziona sidebar `Audio Converter`
3. Clicca `Select audio from Media Library`
4. Nel modal media scegli un file audio (la libreria e' filtrata su `audio`)
5. Imposta eventuali opzioni editoriali e proper noun hints
6. Scegli `Editor insertion mode`:
  - `Append (recommended)`: aggiunge i nuovi blocchi in fondo al post corrente
  - `Replace`: sostituisce i blocchi attuali nel post corrente
7. Clicca `Generate draft from audio`
8. Attendi il messaggio di esito

Esito atteso:
- `success`: bozza creata, con URL restituito nel messaggio
- `failed`: errore strutturato con motivo

## Come avviene la selezione audio in Gutenberg

La sidebar usa `wp.media(...)` con:
- `library.type = 'audio'`
- `multiple = false`

Questo apre il selettore media nativo WordPress mostrando solo allegati audio, e il plugin salva il `media_id` selezionato.

## Endpoint Abilities API (core)

- Metodo: `POST`
- Path principale: `/wp-json/wp-abilities/v1/audio-converter-for-wp/audio-to-post/run`
- Path alternativo (compat WP): `/wp-json/wp-abilities/v1/abilities/audio-converter-for-wp/audio-to-post/run`
- URL esempio (Postman): `https://audioconverter.wordpress.cloud/wp-json/wp-abilities/v1/audio-converter-for-wp/audio-to-post/run`
- Auth: nonce WordPress lato editor (`X-WP-Nonce`)

Payload minimo (core run endpoint):
- Il body deve contenere la chiave top-level `input` (oggetto)
- Dentro `input` vanno i campi del contratto:
  - `contract_version`
  - `external_run_id`
  - `source`
  - `audio` (nel flusso editor usa `media_id`)

Nota:
- `media_options` e' attualmente riservato per evoluzioni future. I campi `featured_image_id` e `gallery_image_ids` sono presenti nello schema ma, in questa versione, non vengono ancora applicati in fase di publish.

Esempio:

```json
{
  "input": {
    "contract_version": "1.0.0",
    "external_run_id": "postman-20260708-001",
    "source": "manual",
    "audio": {
      "media_id": 1755
    }
  }
}
```

## Troubleshooting rapido

1. La sidebar non compare
- Verifica che il plugin sia attivo
- Verifica di essere nel Block Editor (non Classic Editor)

2. Errore AI provider unavailable
- Controlla AI Connectors in WordPress
- Verifica modello/provider attivo e credenziali

3. Errore su selezione audio
- Verifica che il file scelto sia realmente audio
- Verifica che l'allegato esista in Media Library

4. Nessuna bozza creata
- Controlla messaggio errore restituito nella sidebar
- Controlla log WordPress se `WP_DEBUG_LOG` e' attivo

## Note tecniche

- Lo stato job usa transients (baseline)
- Per produzione ad alto volume e' consigliata tabella dedicata per job/idempotenza

## Localizzazione (i18n)

- Text domain plugin: `audio-converter-for-wp`
- Template traduzioni: `languages/audio-converter-for-wp.pot`
- La sidebar Gutenberg e' pronta per localizzazione multi-lingua tramite `wp.i18n.__`

Rigenera il POT (dalla root workspace):

```bash
wp i18n make-pot plugins/audio-converter-for-wp plugins/audio-converter-for-wp/languages/audio-converter-for-wp.pot --slug=audio-converter-for-wp
```

## Roadmap

### Prossimi passi imminenti

- [ ] **README & WordPress.org** — Aggiornare il README con istruzioni d'uso complete e preparare il plugin per la submission nella directory ufficiale di WordPress.org (asset grafici, screenshot, `readme.txt` in formato WP.org).
- [ ] **Test Postman** — Testare l'ability del plugin tramite Postman: chiamate dirette all'endpoint core Abilities API (`.../wp-abilities/v1/.../run`) con scenari success/failed, verifica del contratto `ability-audio-to-post-v1`.
- [ ] **Migrazione su GitHub** — Spostare il progetto su un repository GitHub (con `.gitignore` appropriato, branch strategy, e protezione del branch `main`).

### Integrazioni future

- [ ] **GitHub Actions + Telegram** — Pipeline CI/CD con GitHub Actions; integrazione Telegram per caricare automaticamente immagini, video e audio da Telegram a WordPress.
- [ ] **Adapter Astro** — Sviluppare un nuovo adapter per il frontend Astro in modo da consumare i contenuti generati dal plugin.

### Documentazione & Tutorial

- [ ] **Tutorial bot Telegram** — Creare un tutorial che guidi gli utenti nella creazione di un bot Telegram collegato a uno script che riceve i media inviati su Telegram e li carica su WordPress tramite l'endpoint nativo `POST /wp-json/wp/v2/media`. Una volta in Media Library, i file sono pronti per essere selezionati dal plugin.

### Versione Pro

- [ ] **Estrazione audio da video** — Estrarre tracce audio da video registrati da smartphone, prima di avviare la pipeline di trascrizione e strutturazione.
- [ ] **Opzioni di configurazione avanzate** — Offrire impostazioni aggiuntive per indicare all'AI il tipo di contenuto audio:
  - Diario della giornata
  - Reportage giornalistico
  - Intervista
  - Ricerca di contenuti sul web
  - (altri tipi personalizzabili)
  
  In base al tipo selezionato, il prompt AI si adegua per ottimizzare il contenuto generato.
