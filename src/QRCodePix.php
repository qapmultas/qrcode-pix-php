<?php
/*
 * QAP Multas - Você sempre pronto para autuar!
 * Criado inspirado no código de Willian Costa (em: https://github.com/william-costa/wdev-qrcode-pix-estatico-php)
 */

namespace QapCorp;

/**
 * Código para QR Code e "Copia e Cola" do PIX, usado para gerar uma cobrança.
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class QRCodePix
{
    public const ID_PAYLOAD_FORMAT_INDICATOR = '00';
    public const ID_POINT_OF_INITIATION_METHOD = '01';
    public const ID_MERCHANT_ACCOUNT_INFORMATION = '26';
    public const ID_MERCHANT_ACCOUNT_INFORMATION_OUTRO = '27';
    public const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
    public const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
    public const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    public const ID_MERCHANT_ACCOUNT_INFORMATION_URL = '25';
    public const ID_MERCHANT_CATEGORY_CODE = '52';
    public const ID_TRANSACTION_CURRENCY = '53';
    public const ID_TRANSACTION_AMOUNT = '54';
    public const ID_COUNTRY_CODE = '58';
    public const ID_MERCHANT_NAME = '59';
    public const ID_MERCHANT_CITY = '60';
    public const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';
    public const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';
    public const ID_CRC16 = '63';

    /**
     * @var bool
     */
    private $dynamic = false;

    /**
     * @var string
     */
    private $pixKey = '';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var string
     */
    private $merchantName = '';

    /**
     * @var string
     */
    private $merchantCategoryCode = '0000';

    /**
     * @var string
     */
    private $merchantOutroGui = '';

    /**
     * @var string
     */
    private $merchantOutroKey = '';

    /**
     * @var string
     */
    private $merchantCity = '';

    /**
     * @var string
     */
    private $txid = '';

    /**
     * @var int
     */
    private $amount = 0;

    /**
     * @var bool
     */
    private $uniquePayment = false;

    /**
     * @var string
     */
    private $url = '';

    /**
     * Se o QRCode vai ser dinâmico (com URL) ou estático.
     */
    public function setDynamic(bool $dynamic): self
    {
        $this->dynamic = $dynamic;

        return $this;
    }

    /**
     * Define a chave PIX. Somente para QR code estático.
     */
    public function setPixKey(string $pixKey): self
    {
        $this->pixKey = $pixKey;

        return $this;
    }

    /**
     * Define se o PIX poderá ser pago apenas uma única vez.
     */
    public function setUniquePayment(bool $uniquePayment): self
    {
        $this->uniquePayment = $uniquePayment;

        return $this;
    }

    /**
     * Define a url para as informações do PIX. Somente para QR code dinâmico.
     */
    public function setUrl(?string $url): self
    {
        if (!empty($url)) {
            $url = \preg_replace('/^https?:\/\//', '', $url);
        }

        $this->url = $url ?? '';

        return $this;
    }

    /**
     * Define a descrição do pagamento. Somente para QR code estático.
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Define o nome do PSP responsável.
     */
    public function setMerchantName(string $merchantName): self
    {
        $this->merchantName = $merchantName;

        return $this;
    }

    /**
     * Define a cidade do PSP responsável.
     */
    public function setMerchantCity(string $merchantCity): self
    {
        $this->merchantCity = $merchantCity;

        return $this;
    }

    /**
     * Define a categoria do PSP responsável.
     */
    public function setMerchantCategoryCode(string $merchantCategoryCode): self
    {
        $this->merchantCategoryCode = $merchantCategoryCode;

        return $this;
    }

    /**
     * Define uma url para um PSP que entende esse valor especificamente.
     * Usado em casos especiais, de acordo com instruções de seu PSP.
     */
    public function setMerchantOutroGui(string $merchantOutroGui): self
    {
        $this->merchantOutroGui = $merchantOutroGui;

        return $this;
    }

    /**
     * Define o número da conta a ser depositada.
     * Usado em casos especiais, de acordo com instruções de seu PSP.
     */
    public function setMerchantOutroKey(string $merchantOutroKey): self
    {
        $this->merchantOutroKey = $merchantOutroKey;

        return $this;
    }

    /**
     * Define o txid da cobrança de PIX gerada.
     */
    public function setTxid(?string $txid): self
    {
        $this->txid = $txid ?? '';

        return $this;
    }

    /**
     * Define o valor da cobrança de PIX, em centavos.
     *
     * Por exemplo, para definir um PIX de "R$ 5,67" use "567".
     */
    public function setAmount(?int $amount): self
    {
        $this->amount = $amount ?? 0;

        return $this;
    }

    /**
     * Cria uma seção (data-object) que compõe o payload do PIX.
     *
     * @param string|string[] $value
     *
     * @return string $id.$size.$value
     */
    private function section(string $id, $value): string
    {
        if (\is_array($value)) {
            $value = \implode('', \array_filter($value));
        }

        if ('' === $value) {
            return '';
        }

        $size = \str_pad($this->getStringLength($value), 2, '0', STR_PAD_LEFT);

        return $id.$size.$value;
    }

    /**
     * Método responsável por retornar os valores completos da informação da conta.
     */
    private function sectionMerchantAccountInformation(): string
    {
        return $this->section(self::ID_MERCHANT_ACCOUNT_INFORMATION, [
            $this->section(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'BR.GOV.BCB.PIX'),

            // chave PIX
            $this->section(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->pixKey),

            // descrição do pagamento
            $this->section(self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $this->description),

            // url só pode ser colocado se o QRcode for dinâmico
            $this->dynamic ? $this->section(self::ID_MERCHANT_ACCOUNT_INFORMATION_URL, $this->url) : '',
        ]);
    }

    /**
     * Método responsável por retornar os valores completos da informação da conta.
     */
    private function sectionMerchantAccountInformationOutro(): string
    {
        return $this->section(self::ID_MERCHANT_ACCOUNT_INFORMATION_OUTRO, [
            // url do PSP
            $this->section(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, $this->merchantOutroGui),

            // account id
            $this->section(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->merchantOutroKey),
        ]);
    }

    /**
     * Retorna o data-object do txid do PIX, caso houver.
     * Em QR code dinâmico, o txid deve sempre ser "***".
     */
    private function sectionAdditionalDataFieldTemplate(): string
    {
        $txid = $this->dynamic ? '***' : $this->txid;

        return $this->section(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, [
            // txid
            $this->section(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $txid),
        ]);
    }

    /**
     * Retorna o data-object no caso do PIX ser um pagamento único.
     */
    private function sectionUniquePayment(): string
    {
        return $this->uniquePayment ? $this->section(self::ID_POINT_OF_INITIATION_METHOD, '12') : '';
    }

    /**
     * Código QR Code do PIX.
     */
    public function getPayload(): string
    {
        $payload = $this->section(self::ID_PAYLOAD_FORMAT_INDICATOR, '01')
            .$this->sectionUniquePayment()
            .$this->sectionMerchantAccountInformation()
            .$this->sectionMerchantAccountInformationOutro()
            .$this->section(self::ID_MERCHANT_CATEGORY_CODE, $this->merchantCategoryCode)
            .$this->section(self::ID_TRANSACTION_CURRENCY, '986') // é o codigo do Real no ISO
            .$this->section(self::ID_TRANSACTION_AMOUNT, ($this->dynamic ? '' : $this->formatAmount($this->amount)))
            .$this->section(self::ID_COUNTRY_CODE, 'BR')
            .$this->section(self::ID_MERCHANT_NAME, $this->merchantName)
            .$this->section(self::ID_MERCHANT_CITY, $this->merchantCity)
            .$this->sectionAdditionalDataFieldTemplate()
        ;

        return $payload.$this->generateCRC16($payload);
    }

    public function __toString(): string
    {
        return $this->getPayload();
    }

    /**
     * Formata um número no formato "9.99".
     */
    private function formatAmount(int $amount): string
    {
        $amountStr = (string) $amount;

        return substr_replace(str_pad($amountStr, 3, '0', STR_PAD_LEFT), '.', -2, 0);
    }

    /**
     * Calcula o código verificador do payload do PIX.
     */
    private function generateCRC16(string $payload): string
    {
        $payload .= self::ID_CRC16.'04';

        // polinômio definido pelo Bacen
        $polynomial = 0x1021;
        $result = 0xFFFF;

        if (0 < $length = strlen($payload)) {
            for ($offset = 0; $offset < $length; ++$offset) {
                $result ^= (ord($payload[$offset]) << 8);
                for ($bitwise = 0; $bitwise < 8; ++$bitwise) {
                    if (($result <<= 1) & 0x10000) {
                        $result ^= $polynomial;
                    }
                    $result &= 0xFFFF;
                }
            }
        }

        return self::ID_CRC16.'04'.\str_pad(\strtoupper(\dechex($result)), 4, '0', \STR_PAD_LEFT);
    }

    /**
     * Tenta usar um contador de caracteres de uma string code-byte safe.
     * Se não conseguir, usa o strlen padrão.
     */
    private function getStringLength(string $string): int
    {
        if (\extension_loaded('mbstring')) {
            return \mb_strlen($string);
        }

        if (\extension_loaded('iconv')) {
            return \iconv_strlen($string);
        }

        return \strlen($string);
    }
}
