# Guida rapida - Versione Free

Questa guida e' pensata per essere usata senza sito di supporto esterno.

## Requisiti

- WordPress 7.0+
- PHP 8.0+
- Almeno un AI Connector WordPress configurato e attivo
- Utente con permesso di modifica articoli

Importante: senza un connettore AI attivo il plugin si attiva, ma non puo' generare bozze da audio.

## 1) Installazione

1. Copia la cartella del plugin in wp-content/plugins.
2. Attiva Audio Converter.
3. Vai in Settings > Audio Converter.
4. Imposta i default e salva.

## 2) Configurazione consigliata

- Default language: lingua principale del sito
- Default tone: professional
- Default target length: medium
- Proper noun hints: nomi propri, brand, luoghi, prodotti
- Editor insertion mode: append (consigliato)

## 3) Uso nel Block Editor

1. Apri o crea un post.
2. Apri la sidebar Audio Converter.
3. Clicca Select audio from Media Library.
4. Seleziona un file audio.
5. Controlla opzioni editoriali.
6. Clicca Generate draft from audio.

## 4) Cosa aspettarti

- Il plugin inserisce blocchi Gutenberg nel post corrente.
- Se attivo, il titolo generato viene applicato al titolo del post.
- In caso di errore, compare un messaggio esplicito in sidebar.

## 5) Troubleshooting veloce

- Sidebar non visibile: verifica che sia Block Editor e plugin attivo.
- Errore AI provider: verifica configurazione AI Connectors.
- Nessun blocco inserito: controlla il messaggio in sidebar e riprova con audio piu' pulito.

## 6) Limiti della versione Free (attuali)

- Flusso orientato all'aggiornamento del post corrente.
- Questa guida copre le funzionalita' operative della release Free.

## 7) Buone pratiche

- Usa audio chiaro e con poco rumore.
- Aggiungi proper noun hints per ridurre errori su nomi propri.
- Per risultati migliori, usa clip brevi e ben scandite.
