# qrcode-pix-php
Classe para gerar o código usado no QR Code e como "Copia e Cola" de um PIX

Inspirado no código por [Willian Costa](https://github.com/william-costa/wdev-qrcode-pix-estatico-php).
Possuí melhorias como suporte para mais *data objects* e corrige um problema no cálculo do polinômio quando
o resultado é menor que 0x100.

___

## Instalação

Via *composer*, execute:
```shell
composer require qapmultas/qrcode-pix
```

Adicione está linha de código em seu front controller ou arquivo que for usar:
```php
require 'vendor/autoload.php';
```

## Requisitos

- PHP 7.1+
- mbstring ou iconv *(opcional)*

## Instruções de uso

Basta criar uma instância e definir seus valores:
```php
use QapCorp\QRCodePix;

$qrcode = new QRCodePix();

// exemplo de estático
$qrcode
    ->setAmount(450) // R$ 4,50
    ->setDescription('Referente ao produto x')
    ->setMerchantCategoryCode('0000') // deve ser de acordo com o seu PSP
    ->setMerchantName('Nome do PSP') // deve ser de acordo com o seu PSP
    ->setMerchantCity('SAO PAULO') // deve ser de acordo com o seu PSP
    ->setPixKey('sua-chave-pix')
    ->setTxid('seutxid123456')
    ->setUniquePayment(true);

// exemplo de dinâmico
$qrcode
    ->setDynamic(true)
    ->setUrl('https://api.site-do-psp.com/9AED1623-219B-4FE9-9A6B-11DC72D771A6') // deve ser de acordo com o seu PSP
    ->setMerchantCategoryCode('0000') // deve ser de acordo com o seu PSP
    ->setMerchantName('Nome do PSP')
    ->setMerchantCity('SAO PAULO')
    ->setUniquePayment(true);

// usando o payload do QR code
echo $qrcode->getPayload(); // ou (string) $qrcode
```

A classe usa setters fluentes para definir seus valores, mas se preferir, você pode fazer as
chamadas linha por linha:
```php
// exemplo de uso não fluente
$qrcode->setAmount(450);
$qrcode->setDescription('Referente ao produto x');
$qrcode->setMerchantCategoryCode('0000');
$qrcode->setMerchantName('Nome do PSP');
$qrcode->setMerchantCity('SAO PAULO');
$qrcode->setPixKey('sua-chave-pix');
$qrcode->setTxid('seutxid123456');
$qrcode->setUniquePayment(true);
```

## Licença de uso

Veja mais em [LICENSE](LICENSE).
