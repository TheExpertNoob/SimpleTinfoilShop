# SimpleTinfoilShop
Some basic PHP files to dynamically load and encrypt tinfoil indexes.  
Uses Tinfoil HAUTH as additional protection.  
Obtain this key by connecting to your domain with Tinfoil on a physical nintendo switch
and reading the web server's logs. Do not share this key!

## Each php file has some configuration that needs to be done to fill in your HAUTH and domain.

Root `index.php` loads in the folders `Retro` and `SXRoms`.  
(can be configured further below in the directories key)

`Retro`'s index.php scans for all supported retro rom extensions inside the `Retro` folder and subfolders.  
(great for organization)

`SXRoms`'s index.php scans for all supported switch rom extensions (nsp, nsz, xci, xcz) inside the `SXRoms` folder and subfolders.  
(great for organization)

## `encrypt` folder and its contents are also needed on the webserver root to encrypt.  
Do not modify its contents.
