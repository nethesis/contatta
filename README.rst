=========
Contatta
=========

Questo repository contiene l'integrazione Contatta-NethServer, in particolare:
- consente di configurare dall'interfaccia di FreePBX le opzioni del dialplan necessarie per Contatta
- configura SAMBA per rendere accessibile /var/spool/asterisk/monitor
- consente di creare ed eliminare con delle REST API degli interni appartenenti al contesto webcall

Installazione
==============

Installare l'RPM di contatta: ::
    yum install contatta-*.ns7.noarch.rpm

Raggiungere l'interfaccia del modulo di FreePBX all'indirizzo https://IP/freepbx/admin/config.php?display=contatta

Configurazione
==============

AGI IP 1: è il primo IP dove si trova l'AGI
AGI IP 2: è il IP dove si trova l'AGI per gestire l'HA
Monitor Exec: consente di scegliere come realizzare le registrazioni
Enable AMI user consente di abilitare un utente per l'AMI
La password per l'utente AMI è random e può essere modificata

Salvando e applicando i cambiamenti, le configurazioni vengono scritte nel dialplan.

REST API
=========

Vengono esposte due API che consento la creazione e l'eliminazione di interni appartenenti al contesto "webcall"

le API possono essere chiamate utilizzando una API KEY generata all'installazione del pacchetto e conservata nel file /var/lib/nethserver/secrets/contatta


POST /freepbx/contatta/extension/<EXTENSION> : crea l'extension <EXTENSION> nel contesto "webcall"

esempio: ::

    curl 'https://192.168.122.75/freepbx/contatta/extension/405' -H 'Secretkey: _UL8Wsvl6lwHH63n' --data '' -kv

genera l'interno 405 nel contesto webcall e restituisce un json contenente eventuali errori o warning e il secret dell'interno: ::
    {
        "errors": [],
        "extension": "405",
        "infos": [],
        "secret": "08ulR13B3lETsbV",
        "status": true,
        "warnings": []
    }

NOTA: gli interni appartengono al contesto webcall e non possono chamare numeri deversi dagli agenti. Tuttavia se venissero modificati dall'interfaccia di FreePBX, il contesto verrebbe perso. Questi interni non vanno modificati dall'interfaccia di FreePBX ma solamente tramite le REST API

DELETE /freepbx/contatta/extension/<EXTENSION> : elimina l'extension <EXTENSION>

esempio: ::

    curl 'https://192.168.122.75/freepbx/contatta/extension/405' -H 'Secretkey: _UL8Wsvl6lwHH63n' -X DELETE -kv

elimina l'extension 405 e restituisce un json contenente eventuali errori o warning: ::

    {
        "errors": [],
        "infos": [],
        "status": true,
        "warnings": []
    }

Certificato
===========

- Versione Community: usare il modulo certman

- Versione Enterprise: il certificato usato è quello di NethServer

Samba
=====

Viene condivisa la cartella /var/spool/asterisk/monitor

G729
====

Per installare ed attivare il codec g729 Open Source ecco la procedura (comporta il riavvio di Asterisk e quindi l’eventuale caduta di chiamate in corso): ::

    cd /usr/lib64/asterisk/modules/
    wget http://asterisk.hosting.lv/bin/codec_g729-ast130-gcc4-glibc-x86_64-pentium4.so
    mv codec_g729-ast130-gcc4-glibc-x86_64-pentium4.so codec_g729.so
    chmod 755 codec_g729.so
    systemctl restart asterisk

Il codec g729 Open Source non è compatibile con la versione a pagamento di Digium, che si può installare seguendo la procedura che vi forniranno con l’acquisto.

E' possibile, quindi, utilizzare contemporaneamente solo una delle due versioni di g729, Open Source o Digium.

Informazioni aggiuntive per lo sviluppo
========================================

RPM
---

l'RPM di contatta contiene:
- un modulo di FreePBX (messo in /usr/src/contatta/modules/contatta.tar.gz ed installato dentro /var/www/html/freepbx/admin/modules/contatta dall'azione /etc/e-smith/events/actions/contatta-update)
- il codice delle REST API, installate in /var/www/html/freepbx/contatta
- la configurazione di Samba: nel frammento di template /etc/e-smith/templates/etc/samba/smb.conf/95contatta espanso dall'evento di installazione contatta-update

La build dell'RPM può essere fatta da un NethServer o da Fedora.
- Installare nethserver-mock http://docs.nethserver.org/projects/nethserver-devel/en/latest/building_rpms.html#nethserver-mock
- lanciare lo script che crea l'archivio del modulo di FreePBX e lo firma. Non è indispensabile essere in possesso di una chiave firmata da Sangoma, ma serve per evitare che nell'interfaccia di FreePBX compaia l'allarme di "moduli non firmati" https://wiki.freepbx.org/display/FOP/Requesting+a+Key+to+be+Signed ::

    ./retrieve_modules.sh GPG-KEY-SIGNATURE GPGPASSPHRASE

- lanciare il comando per creare l'RPM: ::

    make-rpms contatta.spec

Il modulo di FreePBX
---------------------

- Il modulo di FreePBX contiene l'interfaccia web di configurazione in /var/www/html/freepbx/admin/modules/contatta/views/form.php e la funzione necessaria per scrivere il dialplan (funzione doDialplanHook dentro /var/www/html/freepbx/admin/modules/contatta/Contatta.class.php)
- le configurazioni del modulo vengono salvate nella tabella mysql asterisk.kvstore_FreePBX_modules_Contatta

Rest API
---------
- le API sono fornite grazie al framework Slim https://www.slimframework.com/
- l'autenticazione è definita in /var/www/html/freepbx/contatta/lib/AuthMiddleware.php e al momento si limita a verificare che la "Secretkey" fornita nell'header sia uguale a quella generata in fase di installazione e salvata in /var/lib/nethserver/secrets/contatta
- le API sono nel file /var/www/html/freepbx/contatta/modules/extensions.php, utilizzano la libreria aggiuntiva /var/www/html/freepbx/contatta/lib/libExtensions.php. E' possibile da qui chiamare tutte le funzioni di FreePBX grazie all'include di /etc/freepbx.conf in /var/www/html/freepbx/contatta/index.php
- Tutti i cambiamenti apportati dalle funzioni di FreePBX vengono salvati sul database mysql di FreePBX (database asterisk) e vengono effettivamente scritte nei file di configurazione di Asterisk quando da interfaccia viene premuto il tasto "Applica cambiamenti". Nel caso delle API, i cambiamenti vengono applicati dallo script /var/www/html/freepbx/contatta/lib/retrieveHelper.sh


