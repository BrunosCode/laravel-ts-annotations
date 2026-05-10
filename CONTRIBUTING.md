# Contributing to laravel-ts-annotations

Grazie per l'interesse nel contribuire! Questa guida copre tutto ciò che serve per lavorare sul pacchetto in locale, aprire issue e proporre modifiche.

---

## Indice

- [Setup dell'ambiente](#setup-dellambiente)
- [Struttura del progetto](#struttura-del-progetto)
- [Eseguire i test](#eseguire-i-test)
- [Workflow per contribuire](#workflow-per-contribuire)
- [Convenzioni di codice](#convenzioni-di-codice)
- [Aprire una issue](#aprire-una-issue)
- [Aprire una pull request](#aprire-una-pull-request)
- [Roadmap e aree aperte](#roadmap-e-aree-aperte)

---

## Setup dell'ambiente

**Requisiti:**
- PHP 8.1+
- Composer 2.x

```bash
# Clona il repository
git clone https://github.com/brunoscode/laravel-ts-annotations.git
cd laravel-ts-annotations

# Installa le dipendenze di sviluppo
composer install
```

Per testare il pacchetto all'interno di un progetto Laravel reale, aggiungi un repository locale nel `composer.json` del progetto:

```json
"repositories": [
    {
        "type": "path",
        "url": "../laravel-ts-annotations"
    }
],
"require": {
    "brunoscode/laravel-ts-annotations": "@dev"
}
```

Poi esegui `composer update brunoscode/laravel-ts-annotations`.

---

## Struttura del progetto

```
laravel-ts-annotations/
│
├── src/
│   ├── Attributes/
│   │   ├── TS.php                    # #[TS(...)] — TypeScript verbatim su classe o metodo
│   │   ├── TSEnum.php                # #[TSEnum] — enum TS auto-generato da PHP enum
│   │   └── TSType.php                # #[TSType] — type alias TS inferito dalle property PHP
│   │
│   ├── Scanner/
│   │   └── PhpFileScanner.php        # Scansiona le directory e ricava i FQCN
│   │                                 # dei file PHP usando il tokenizer, senza
│   │                                 # fare require delle classi
│   │
│   ├── Parser/
│   │   ├── AttributeParser.php       # Legge #[TS], #[TSEnum], #[TSType] via Reflection
│   │   │                             # e raggruppa i body per chiave di output
│   │   └── PhpToTsTypeMapper.php     # Mappa i tipi PHP nativi nei corrispettivi TypeScript
│   │
│   ├── Writer/
│   │   └── TypeScriptFileWriter.php  # Scrive il .ts gestendo i marcatori:
│   │                                 # preserva tutto fuori dai marcatori,
│   │                                 # sostituisce solo la sezione generata
│   │
│   ├── Commands/
│   │   └── GenerateTypesCommand.php  # Comando Artisan `ts:generate`
│   │
│   └── TsAnnotationsServiceProvider.php
│
├── config/
│   └── ts-annotations.php            # Config pubblicabile con vendor:publish
│
├── tests/
│   ├── TestCase.php                  # Base con Orchestra Testbench
│   ├── Fixtures/
│   │   ├── UserResource.php          # Classe con #[TS] (livello classe)
│   │   ├── UserController.php        # Classe con #[TS] (livello metodo)
│   │   ├── UserData.php              # DTO con #[TSType] e promoted constructor params
│   │   ├── ProductData.php           # Classe con #[TSType] e property regolari
│   │   ├── StatusEnum.php            # Enum string-backed con #[TSEnum]
│   │   ├── PriorityEnum.php          # Enum int-backed con #[TSEnum]
│   │   └── DirectionEnum.php         # Enum unit (senza backing type) con #[TSEnum]
│   ├── Unit/
│   │   ├── WriterTest.php            # Test isolati sulla logica del Writer
│   │   ├── AttributeParserTest.php   # Test su #[TS] (classe e metodi)
│   │   ├── EnumParserTest.php        # Test su #[TSEnum]
│   │   └── TSTypeParserTest.php      # Test su #[TSType]
│   └── Feature/
│       └── GenerateTypesCommandTest.php  # Test end-to-end del comando Artisan
│
└── .github/
    └── workflows/
        └── tests.yml                 # CI: matrice PHP 8.1–8.4 × Laravel 10–12
```

### Flusso dati

```
ts:generate
    │
    ├── PhpFileScanner::scan(paths)
    │       Ricerca ricorsiva dei .php → estrazione FQCN con token_get_all()
    │
    ├── AttributeParser::parse(fqcns)
    │       ReflectionClass per ogni FQCN:
    │         • #[TS]      → body verbatim (classe o metodo)
    │         • #[TSEnum]  → body generato da ReflectionEnum::getCases()
    │         • #[TSType]  → body generato da ReflectionProperty (public, non-static)
    │                        con PhpToTsTypeMapper per la traduzione dei tipi
    │       Risultato: [ 'default' => [['class' => ..., 'body' => ...], ...] ]
    │
    └── TypeScriptFileWriter::write(path, entries, imports)
            Se file non esiste   → crea con blocco generato
            Se marcatori trovati → sostituisce solo la sezione tra essi
            Se no marcatori      → appende in fondo
```

### Logica di generazione per `#[TSEnum]`

`AttributeParser::generateEnumBody()` usa `ReflectionEnum`:
- Enum backed string → `CaseName = 'backing_value',`
- Enum backed int → `CaseName = backing_value,` (senza apici)
- Enum unit (nessun tipo) → `CaseName = 'CaseName',`

### Logica di generazione per `#[TSType]`

`AttributeParser::generateTypeBody()` raccoglie le property public non-static dichiarate nella classe (escluse quelle ereditate). Per ogni property:
- Il tipo PHP viene tradotto tramite `PhpToTsTypeMapper::map()`
- Le property `readonly` ricevono il modificatore `readonly` anche in TypeScript
- Il parametro opzionale `name` di `#[TSType]` sovrascrive il nome breve della classe

---

## Eseguire i test

```bash
# Suite completa
vendor/bin/phpunit

# Solo i test unitari
vendor/bin/phpunit --testsuite Unit

# Solo i test di feature
vendor/bin/phpunit --testsuite Feature

# Un singolo test
vendor/bin/phpunit --filter test_replaces_generated_section_preserving_manual_content

# Con output verboso
vendor/bin/phpunit --testdox
```

Per testare contro una versione specifica di Laravel (come fa la CI):

```bash
composer require "laravel/framework:^11.0" "orchestra/testbench:^9.0" --no-update
composer update --prefer-stable
vendor/bin/phpunit
```

---

## Workflow per contribuire

1. **Apri una issue** prima di iniziare a lavorare su feature significative, così possiamo discutere l'approccio.
2. **Fai un fork** del repository e crea un branch dedicato:
   ```bash
   git checkout -b feat/nome-feature
   # oppure
   git checkout -b fix/nome-bug
   ```
3. **Scrivi i test prima** del codice quando possibile — segui lo stile dei test esistenti.
4. **Assicurati che tutti i test passino** prima di aprire la PR:
   ```bash
   vendor/bin/phpunit
   ```
5. **Apri la pull request** verso il branch `main` con una descrizione chiara di cosa fa e perché.

---

## Convenzioni di codice

- **PSR-12** per la formattazione PHP.
- **Nomi descrittivi** per classi, metodi e variabili — evita abbreviazioni.
- **Un file, una classe** — non mettere più classi nello stesso file.
- **Docblock** sui metodi pubblici con i tipi dei parametri e del ritorno, specialmente sulle API pubbliche del pacchetto.
- **Nessuna dipendenza esterna** oltre a `illuminate/console` e `illuminate/support` — il pacchetto deve restare leggero.
- Nella `TypeScriptFileWriter`, il corpo TypeScript va sempre passato attraverso `normalizeBody()` prima di scriverlo — non scrivere mai il raw body direttamente.
- In `PhpToTsTypeMapper`, aggiungi nuove mappature nelle costanti `SCALARS` o `CLASS_OVERRIDES` — non disperdere la logica di mapping nei metodi dell'`AttributeParser`.

---

## Aprire una issue

Usa i template GitHub quando disponibili. In ogni caso, includi sempre:

**Per bug:**
- Versione PHP e Laravel in uso
- L'attributo o il tipo PHP che causa il problema
- L'output attuale e quello atteso
- Se possibile, un caso riproducibile minimale

**Per feature request:**
- Problema che vuoi risolvere
- Proposta di API (come verrebbe usato il pacchetto con la nuova feature)
- Alternative considerate

---

## Aprire una pull request

La descrizione della PR deve contenere:

- **Cosa fa** — un paragrafo chiaro sul comportamento introdotto o modificato
- **Perché** — il problema che risolve o la feature che aggiunge
- **Come testarlo** — i passi per verificare manualmente il comportamento, se rilevante
- **Breaking changes** — segnala esplicitamente qualsiasi modifica che rompe la compatibilità con versioni precedenti

La CI esegue i test su tutte le combinazioni supportate di PHP e Laravel. Tutte le combinazioni devono risultare verdi prima del merge.

---

## Roadmap e aree aperte

Queste sono le aree dove i contributi sono particolarmente benvenuti:

- **`--watch` flag** — rigenerazione automatica all'aggiornamento di un file PHP.
- **`#[TSHide]`** — attributo companion per escludere singole property dalla generazione di `#[TSType]`.
- **Supporto PHPDoc** — leggere `@var` e `@property` per tipi più ricchi in `#[TSType]` (es. `array<string>` → `string[]`).
