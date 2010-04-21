<?php //vim: foldmethod=marker
//require_once("OAuth.php");

class TestOAuthServer extends OAuthServer {
  public function get_signature_methods() {
    return $this->signature_methods;
  }
}

class TestOAuthSignatureMethod_RSA_SHA1 extends OAuthSignatureMethod_RSA_SHA1 {
  public function fetch_private_cert(&$request) {
    $cert = <<<EOD
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALRiMLAh9iimur8V
A7qVvdqxevEuUkW4K+2KdMXmnQbG9Aa7k7eBjK1S+0LYmVjPKlJGNXHDGuy5Fw/d
7rjVJ0BLB+ubPK8iA/Tw3hLQgXMRRGRXXCn8ikfuQfjUS1uZSatdLB81mydBETlJ
hI6GH4twrbDJCR2Bwy/XWXgqgGRzAgMBAAECgYBYWVtleUzavkbrPjy0T5FMou8H
X9u2AC2ry8vD/l7cqedtwMPp9k7TubgNFo+NGvKsl2ynyprOZR1xjQ7WgrgVB+mm
uScOM/5HVceFuGRDhYTCObE+y1kxRloNYXnx3ei1zbeYLPCHdhxRYW7T0qcynNmw
rn05/KO2RLjgQNalsQJBANeA3Q4Nugqy4QBUCEC09SqylT2K9FrrItqL2QKc9v0Z
zO2uwllCbg0dwpVuYPYXYvikNHHg+aCWF+VXsb9rpPsCQQDWR9TT4ORdzoj+Nccn
qkMsDmzt0EfNaAOwHOmVJ2RVBspPcxt5iN4HI7HNeG6U5YsFBb+/GZbgfBT3kpNG
WPTpAkBI+gFhjfJvRw38n3g/+UeAkwMI2TJQS4n8+hid0uus3/zOjDySH3XHCUno
cn1xOJAyZODBo47E+67R4jV1/gzbAkEAklJaspRPXP877NssM5nAZMU0/O/NGCZ+
3jPgDUno6WbJn5cqm8MqWhW1xGkImgRk+fkDBquiq4gPiT898jusgQJAd5Zrr6Q8
AO/0isr/3aa6O6NLQxISLKcPDk2NOccAfS/xOtfOz4sJYM3+Bs4Io9+dZGSDCA54
Lw03eHTNQghS0A==
-----END PRIVATE KEY-----
EOD;
    return $cert;
  }

  public function fetch_public_cert(&$request) {
    $cert = <<<EOD
-----BEGIN CERTIFICATE-----
MIIBpjCCAQ+gAwIBAgIBATANBgkqhkiG9w0BAQUFADAZMRcwFQYDVQQDDA5UZXN0
IFByaW5jaXBhbDAeFw03MDAxMDEwODAwMDBaFw0zODEyMzEwODAwMDBaMBkxFzAV
BgNVBAMMDlRlc3QgUHJpbmNpcGFsMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKB
gQC0YjCwIfYoprq/FQO6lb3asXrxLlJFuCvtinTF5p0GxvQGu5O3gYytUvtC2JlY
zypSRjVxwxrsuRcP3e641SdASwfrmzyvIgP08N4S0IFzEURkV1wp/IpH7kH41Etb
mUmrXSwfNZsnQRE5SYSOhh+LcK2wyQkdgcMv11l4KoBkcwIDAQABMA0GCSqGSIb3
DQEBBQUAA4GBAGZLPEuJ5SiJ2ryq+CmEGOXfvlTtEL2nuGtr9PewxkgnOjZpUy+d
4TvuXJbNQc8f4AMWL/tO9w0Fk80rWKp9ea8/df4qMq5qlFWlx6yOLQxumNOmECKb
WpkUQDIDJEoFUzKMVuJf4KO/FJ345+BNLGgbJ6WujreoM1X/gYfdnJ/J
-----END CERTIFICATE-----
EOD;
    return $cert;
  }
} 

/**
 * A mock store for testing
 */
class MockOAuthDataStore extends OAuthDataStore {/*{{{*/
    private $consumer;
    private $request_token;
    private $access_token;
    private $nonce;

    function __construct() {/*{{{*/
        $this->consumer = new OAuthConsumer("key", "secret", NULL);
        $this->request_token = new OAuthToken("requestkey", "requestsecret", 1);
        $this->access_token = new OAuthToken("accesskey", "accesssecret", 1);
        $this->nonce = "nonce";
    }/*}}}*/

    function lookup_consumer($consumer_key) {/*{{{*/
        if ($consumer_key == $this->consumer->key) return $this->consumer;
        return NULL;
    }/*}}}*/

    function lookup_token($consumer, $token_type, $token) {/*{{{*/
        $token_attrib = $token_type . "_token";
        if ($consumer->key == $this->consumer->key
            && $token == $this->$token_attrib->key) {
            return $this->$token_attrib;
        }
        return NULL;
    }/*}}}*/

    function lookup_nonce($consumer, $token, $nonce, $timestamp) {/*{{{*/
        if ($consumer->key == $this->consumer->key
            && (($token && $token->key == $this->request_token->key)
                || ($token && $token->key == $this->access_token->key))
            && $nonce == $this->nonce) {
            return $this->nonce;
        }
        return NULL;
    }/*}}}*/

    function new_request_token($consumer) {/*{{{*/
        if ($consumer->key == $this->consumer->key) {
            return $this->request_token;
        }
        return NULL;
    }/*}}}*/

    function new_access_token($token, $consumer) {/*{{{*/
        if ($consumer->key == $this->consumer->key
            && $token->key == $this->request_token->key) {
            return $this->access_token;
        }
        return NULL;
    }/*}}}*/
}/*}}}*/
?>
