Emailer
=======

## Obsah

* [Základní popis](#základní-popis)
* [Instalace](#instalace)
* [Použití](#použití)
    * [Přidání vlastní akce emaileru](#přidání-vlastní-akce-emaileru)
* [Konfigurace](#konfigurace)
    * [`defaultMailOptions`](#defaultmailoptions)
    * [`adminEmails`](#adminemails)

## Základní popis

Odesílání mailů jednotlivě, např. po nějaké akci, registraci, chybě či formou menší rozesílky, kdy se postupně zpracovává fronta mailů.

**Výhody oproti funkci `mail()`**
* Přehled všech typů odesílaných e-mailů (v Emailer třídě) - pomůže v budoucnu jednoduchému refaktoringu, nebo např. změně vzhledu všech zasílaných e-mailů z jednoho místa.
* Odeslání formuláře na webu proběhne zdánlivě okamžitě - nemusí se čekat třeba 1-2 vteřiny, než stihne vytížený mail server zprávu odeslat.
* Respektuje se výchozí nastavení z NEONu - tzn. spouští se projekt a já, jako hlavní vývojář, se třeba můžu dočasně přidat do BCC, aby mi ve skryté kopii chodily všechny e-maily zasílané uživatelům (třeba k tomu, abych pár dní sledoval, zda všem uživatelů odchází e-maily v očekávané kvalitě).
* Implementace dalšího typů e-mailů je pro vývojáře jednoduchá - jenom zkopíruje v e-maileru metodu, přejmenuje jí, upraví si parametry, volitelně si zkopíruje i šablonu HTML e-mailu, upraví texty a je to. Žádné zkoumání, jak vůbec to HTML e-mailu připravit, jak řešit `Latte\Engine`, atd.
* Máme hezký přehled o každém odeslaném e-mailu (komu, co, kdy, s jakým výsledkem, jak dlouho se posílalo, atd.).
* V balíčku máme hezké statistiky a grafy, kde je vidět i to, jaké typy e-mailů se jak posílali v čase.
* E-mail se odešle co nejrychleji to bude možné (i když bude mít třeba mailserver výpadek, e-mail se odešle hned jak mailserver naběhne).
* Když se e-mail nepodaří odeslat, máme tam auto-retry logiku - když např. vzdálený SMTP server klienta chvíli nefunguje, dáme jeho odeslání několik pokusů.
* Časem můžeme do balíčku udělat i Nagios plugin (URL), který bude Nagiosu říkat důležité informace - např. počet odeslaných e-mailů za požadovaný čas. Nagios nám díky tomu pošle SMS, když se najednou posílá nějak podivně hodně e-mailů (upozornění na možný útok), anebo naopak, že za posledních 24 hodin nebyl odeslaný žádný e-mail, co třeba u e-shopu může poukazovat na naší chybu (nefunguje zpracování fronty e-mailů, nebo nefunguje formulář, atp.).

## Instalace

Doporučený způsob instalace je pomocí [Composeru](http://getcomposer.org/):

```sh
$ composer require mati-core/email
```

Tím dojde ke stažení balíčku a integraci do projektu. Balíček je téměř připravený k použití.

**Logování**

Pro správné logování odeslaných mailů je nutné nastavit práva pro zápis do adresáře `/log/emailer`.

**Základní nastavení**

Dále je nutné zkontrolovat konfiguraci v `/app/config/emailer.neon` a nastavit tam email odesílatele (`from`) a email na administrátora(y) (`adminEmails`). Více o možnostech konfigurace [níže](#konfigurace).

Přidat `App\ConsoleModule\RunEmailerCommand` do sekce `commands:` v `/app/config/console.neon`:

```yaml
console:
    commands:
        - App\ConsoleModule\RunEmailerCommand
```

**CRON**

Pro automatické spouštění emaileru a odesílání emailů ve frontě je nutné ručně zanést task do `/cron/.crontab` a ujistit se, že má `/cron/run-emailer` dostatečná práva pro spuštění. Jméno uživatele a pořadí skriptu za prefixem se může lišit.

```sh
# Kazdou minutu se spusti kontrola pro odesilani emailu z fronty
*/1  *   * * *   apache  /usr/bin/solo -port={SOLO_PORT_PREFIX}01 {ROOT_DIR}cron/run-emailer    # ENVIRONMENTS: production
```

**Automaticky provedené změny v projektu**

Kromě samotného balíčku se přímo do projektu zaneslo:

* databázová tabulka `emailer__email`
* databázová tabulka `emailer__email_raw`
* databázová tabulka `emailer__template` s výchozí šablonou `mail-message`
* model emaileru `/app/model/Emailer.php` přístupný v presenterech
* šablona předpřipravené akce `->sendMessageToAdministrators()` ve složce `/app/_FrontModule/presenters/templates/_Email/`
* založil se adresář pro logy `/log/emailer`
* přidal se cron task `/cron/run-emailer` (command `app:runEmailer`)

## Použití

V presenterech je Emailer přístupný přes `$this->emailer`. Můžeme zavolat např. předpřipravenou akci pro odeslání zprávy administrátorům webu:

```php
<?php
$this->emailer->sendMessageToAdministrators('Import dat', 'Proběhl pravidelný noční import dat, bla bla ...');
```

#### Přidání vlastní akce emaileru

Pro přidání vlastní akce stačí v modelu emaileru (`/app/model/Emailer.php`) přidat metodu a předat jí z presenteru požadované parametry.

Nová metoda by měla založit instanci `MatiCore\Email\Message` (rozšiřuje standardní `Nette\Mail\Message`), nastavit ji a přidat do fronty pomocí privátní metody `->insertMessageToQueue($message, 'mail-message')`.

Je možné připravit i vlastní hezkou šablonu mailu. S tím pomůže další interní metoda `->createTemplateForFile($filepath)`. Šablonu doporučuji umístit do `/app/_FrontModule/presenters/templates/_Email/`, protože na tuto cestu lze odkázat pomocí `$this->templatesDir`.

## Konfigurace

Konfigurační soubor se nachází v `/app/config/emailer.neon`. Obsahuje dva konfigurovatelné parametry.

#### defaultMailOptions

Výchozí nastavení odesílatele a příjemců.


| Položka  | Datový typ    | Popis                                                                     |
|----------|---------------|---------------------------------------------------------------------------|
| from     | string        | e-mail odesílatele                                                        |
| fromName | string        | jméno odesílatele                                                         |
| to       | string, array | e-mail příjemce,  je možné uvést jeden e-mail i pole e-mailů              |
| cc       | string, array | e-mail příjemce kopie,  je možné uvést jeden e-mail i pole e-mailů        |
| bcc      | string, array | e-mail příjemce skryté kopie,  je možné uvést jeden e-mail i pole e-mailů |

```yaml
parameters:
	defaultMailOptions:
		from: noreply@example.com
		fromName: "Example Inc."
		to: []
		cc: []
		bcc: []
```

#### adminEmails

Administrátoři daného projektu, kterým se budou posílat ruzné emailové notifikace.

```yaml
parameters:
	adminEmails:
	    - admin@example.com
	    - jon.doe@example.com
	    - bug@example.com
```
