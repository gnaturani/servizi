<?php

/**
 * DKIM is used to sign e-mails. If you change your RSA key, apply modifications to
 * the DNS DKIM record of the mailing (sub)domain too !
 * Disclaimer : the php openssl extension can be buggy with Windows, try with Linux first
 * 
 * To generate a new private key with Linux :
 * openssl genrsa -des3 -out private.pem 1024
 * Then get the public key
 * openssl rsa -in private.pem -out public.pem -outform PEM -pubout
 */

// Edit with your own info :

define('MAIL_RSA_PASSPHRASE', '');

define('MAIL_RSA_PRIV',
'-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA2eARhHBbNC5k4qxKL00w8FZX7SIzE1Ko3kq7Ub0FDub6RvFS
fDnPmcH62Wo5kCymhOSO/0p+VHOHm3AtVWCoLcUiLoz0rrK6NJCDnDPHtUEI6QVv
SpDm2537XtyDcObC4OfYoM6ljy2nn5Bmh2gYt6dyuY+vxNZW+ktKW6dyFQfaSAjW
l/uAcFiUJL6Lc7DkqYbe4Luwxgtb/ptpxQhGvstJ2UE7XrxK3hGHnhS7LpWl+Bbl
U+caqnoPr7hmAjqmIV/nrikVFfOc62YRXo28yrMIMiXdn5LixZjR7X7DxHXn4rW8
0WdIT8G/1qolw1ilKDFMHhQynaB7+ya6fE7y4QIDAQABAoIBAQDMPraNPjrxnvBq
YnMlBqrzEy5YGfBhk+LEiLAzvwvH3ZYP/ViDJjrMfEFpoaAW3RS5jf3TqwTkrG0a
tT16RSNDzQLvOqqCPwA6GKOYQh5cd3wf3j1nXJFeniow0m3R4DIeXpoAndgscfMq
rVbAZ0CMokf1VpLC5uAgwYYSh9V1ie0sRUkS+9OYYyuHb13sAo1wf6Nzue/tgZIB
ABIxGGtEeRj2W76VNbjwc62PVq4LSdi2I1NA89x01Khmzxs3XOuQLzgRSzSORKFD
tL0F1yGT19TvqovjkJLerA3xsEpC9mBJEsA5bHxti59htrgayBjMyLrPvKy/GiIR
4fnYmBLFAoGBAO9QbGMLjsYr1ct1KyUIV8qo2ZE1SFfPJOoXi4SFcyBO8VRq0CIU
SlAc/pNDbW8RF1y7jbeOlThy/N2qUeuWBY3yU2abWOwSX3H1roF2zEl7La4Slqdk
9fc3ZjiZXS4qMMLAja5/fsjwSZsh0DchdNl9hhFICuAwmNkCZp/4zMA7AoGBAOkQ
+iVrKTe9UVgoMMCJ8HHQp0lhaGghSJOm4BRyIN0hLvxuCwGtZhc/HGeo4I19RxEU
Yjzdvt3POrQkZUHY3cYathUOyFokwS84nKl1Uw5I88RFsD2EL3rKZH+x85LygDwB
9oxRKd0l8Phtif/11sNqsVTxmSXXYI+iF4E6laOTAoGAKGeitSJJa8oQ4bYZn7oF
4JCbkzm0yiaOK/vnsWs6odTSSBd0ppxYY6hRjxmOS3dOQ3jjF3+6T/qSGPbdt/Hv
ZCTq0eMeo1UCymHZocAmA64Ja192EjMomCHBX4L9SYMUEn2iLjkWdeSj+M4/sl8y
tFnOHfLU6z8pP1J5cz71iusCgYBVn7wWtSjeZnoVBibrBYJFfh+HUPb3korEXAFk
4Yz7UG6fpJn8ksS386Ku3pcoxAaw2qlArUKq4LAzcE+XAmJvnm6Yi+bFX01t2MGN
bCIIVHrh96xI3WBIH0UOuMTAjsDXyuzWHhdgPMkrq6qQU7QD9RWTHHNkOJ0sB6PV
AT3qawKBgCucH3rZvn5twgYPl3SnFogevQjh+B6nrHHkFCsmoT0ycGKZXuVQui2P
VTsucd7StxBDuG7htcbAYvBavxqCBdWeQNxcuVnTU1Uy9CzJwOAvK+tbnkHYTTAu
XFFAl4He+BFNPmcyFC/Yhshfb4kGdGl747iOKqmcwJxb52kirKVr
-----END RSA PRIVATE KEY-----');

define('MAIL_RSA_PUBL',
'-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2eARhHBbNC5k4qxKL00w
8FZX7SIzE1Ko3kq7Ub0FDub6RvFSfDnPmcH62Wo5kCymhOSO/0p+VHOHm3AtVWCo
LcUiLoz0rrK6NJCDnDPHtUEI6QVvSpDm2537XtyDcObC4OfYoM6ljy2nn5Bmh2gY
t6dyuY+vxNZW+ktKW6dyFQfaSAjWl/uAcFiUJL6Lc7DkqYbe4Luwxgtb/ptpxQhG
vstJ2UE7XrxK3hGHnhS7LpWl+BblU+caqnoPr7hmAjqmIV/nrikVFfOc62YRXo28
yrMIMiXdn5LixZjR7X7DxHXn4rW80WdIT8G/1qolw1ilKDFMHhQynaB7+ya6fE7y
4QIDAQAB
-----END PUBLIC KEY-----');

// Domain or subdomain of the signing entity (i.e. the domain where the e-mail comes from)
define('MAIL_DOMAIN', 'parrocchiacarpaneto.com');  

// Allowed user, defaults is "@<MAIL_DKIM_DOMAIN>", meaning anybody in the MAIL_DKIM_DOMAIN
// domain. Ex: 'admin@mydomain.tld'. You'll never have to use this unless you do not
// control the "From" value in the e-mails you send.
define('MAIL_IDENTITY', 'postmaster@parrocchiacarpaneto.com');

// Selector used in your DKIM DNS record, e.g. : selector._domainkey.MAIL_DKIM_DOMAIN
define('MAIL_SELECTOR', 'phpmailer._domainkey.parrocchiacarpaneto.com');
