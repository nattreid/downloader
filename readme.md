# Downloader pro Nette Framework

Nastavení v **config.neon**
```neon
extensions:
    - NAttreid\Downloader\DI\DownloaderExtension
```

## Použití
```php
/** @var \NAttreid\Downloader\IDownloader */
private $downloaderFactory;

function download() {
    $downloader = $this->downloaderFactory->create();
    $downloader->index = TRUE; // porovnava hlavicku stahnutych souboru s originaly a pokud se shoduji, nic nestahuje
    $downloader->download([
        'http://zdrojovaAdresa' => 'cilovySoubor'
    ]);
}
```