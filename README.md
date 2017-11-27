# Ringbahnwürfeln
Webapp für eine gemütliche Runde Ringbahnwürfeln.

![Screenshot](/image.png?raw=true "Screenshot")

## Nutzung
Im Menü zuerst Tour wählen, dann Team wählen, wenn man mitwürfeln will, dann muss das teamsecret vorher eingetippt werden. Neues Team kann man auch anlegen (sollte obvious sein). Zum reset kann man "Kekse aufessen" klicken, das löscht die Cookies.

Um den aktuellen Stand zu kriegen, klickt man auf den refresh Button über der Teamliste.

Zum Würfeln klickt man auf den Würfel in der Mitte der Ringbahn. Danach wird das Team für `x` Minuten geblockt und kann nicht mehr würfeln vorher. Die gewürfelte Zahl wird in der Mitte angezeigt, die Zielstation durch den Zeiger.

## Setup
* Ordner `gamefiles` Schreib/Leserechte geben
* Passwort (plain-text) in `gamefiles/adminToken` setzen, keine linebreaks!
* Zeitblockade nach Würfeln ist in `$block_time = 6*10;` gesetzt (`Game.php`)

## Neue Tour
Eine neue Tour kann man remote anlegen zusammen mit dem Admin-Token. Dazu folgende URL öffnen:
`<URL>/Game.php?t=&g=<name>&adminToken=<secretToken>&start=<startStationName>`, wobei `<secretToken>` in `gamefiles/adminToken`, `<game>` der eine Bezeichnung (idealerweise nur alhanumerisch ohne spaces oder sonstwas), und `<startStationName>` plaintext, case-sensitive Name der Startstation (z.B. Tempelhof).
