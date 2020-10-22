<?php
    $spBaseUrl = 'https://localhost/est2-test/saml'; //php-saml‚Ö‚ÌURL‚ğƒZƒbƒg
    $ldpBaseUrl = 'https://zhangwei13-dev-ed.my.salesforce.com';

    $settingsInfo = array (
    	'strict' => true,
    	'debug' => true,
        'sp' => array (
            'entityId' => $spBaseUrl.'/metadata.php',
            'assertionConsumerService' => array (
                'url' => $spBaseUrl.'/index.php?acs',
            ),
            'singleLogoutService' => array (
                'url' => $spBaseUrl.'/index.php?sls',
            ),
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
        ),
        'idp' => array (
            'entityId' => $ldpBaseUrl,
            'singleSignOnService' => array (
                'url' => $ldpBaseUrl.'/idp/endpoint/HttpRedirect',
            ),
            'singleLogoutService' => array (
                'url' => $ldpBaseUrl.'/services/auth/idp/saml2/logout',
            ),
            'x509cert' => 'MIIErDCCA5SgAwIBAgIOAXRi24TxAAAAAGA71h0wDQYJKoZIhvcNAQELBQAwgZAxKDAmBgNVBAMMH1NlbGZTaWduZWRDZXJ0XzA2U2VwMjAyMF8wOTU5NDcxGDAWBgNVBAsMDzAwRDJ4MDAwMDA3NGpNRDEXMBUGA1UECgwOU2FsZXNmb3JjZS5jb20xFjAUBgNVBAcMDVNhbiBGcmFuY2lzY28xCzAJBgNVBAgMAkNBMQwwCgYDVQQGEwNVU0EwHhcNMjAwOTA2MDk1OTQ3WhcNMjEwOTA2MDAwMDAwWjCBkDEoMCYGA1UEAwwfU2VsZlNpZ25lZENlcnRfMDZTZXAyMDIwXzA5NTk0NzEYMBYGA1UECwwPMDBEMngwMDAwMDc0ak1EMRcwFQYDVQQKDA5TYWxlc2ZvcmNlLmNvbTEWMBQGA1UEBwwNU2FuIEZyYW5jaXNjbzELMAkGA1UECAwCQ0ExDDAKBgNVBAYTA1VTQTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAKwmJmBEAymNIUrmWNX+I/2kNBEdXLoT/XEEEvgGPyT//dhR4UhNj16mKroDvSgq0h0r5jOn2m+Ze+IU19COD7nP/ePv8pbRxuaV/K6E4bWBpldy9t2fM6HIzQv6n/NgQif+y9Noq1yZND9BnWr5hqIrQzSQti2uaOIKD1wz+MBdDZM4TBgv/cqeY4uKjOGUd2jlq4icgKmF3wi2tTqEox4ZeBYz9DwI35N6Iaq44IXAl8+sP0sPNw/zQgR17fdxmzH2fbbpUfLOk8PIUTUkC7xMLdfn+UevYcQKOHFq/RV2CziUnOcM6imu+fvLtkEs8je4224t96VWe9XgrMZaWlUCAwEAAaOCAQAwgf0wHQYDVR0OBBYEFPTCP32NMEXInmmKEeCz3Zx3mDwBMA8GA1UdEwEB/wQFMAMBAf8wgcoGA1UdIwSBwjCBv4AU9MI/fY0wRcieaYoR4LPdnHeYPAGhgZakgZMwgZAxKDAmBgNVBAMMH1NlbGZTaWduZWRDZXJ0XzA2U2VwMjAyMF8wOTU5NDcxGDAWBgNVBAsMDzAwRDJ4MDAwMDA3NGpNRDEXMBUGA1UECgwOU2FsZXNmb3JjZS5jb20xFjAUBgNVBAcMDVNhbiBGcmFuY2lzY28xCzAJBgNVBAgMAkNBMQwwCgYDVQQGEwNVU0GCDgF0YtuE8QAAAABgO9YdMA0GCSqGSIb3DQEBCwUAA4IBAQBmrZlA5bDS8ocFxA1Vl0BE1GBPXmkaPXsq0dUtkaR/bmKuc8Y9hY0HV+U5Efr80HwvNin8kIvm0yqpND7NhwUaAD87wQUmMJf69Y55qWk5pLcgL/LZKYPpwaPUvbZ79+QQ28VUJxeRJsjbxA6yoxPKu9q459W/9NUUGidPoyMaGvt2ovg60qQJipKdv7MvH7GwV6PfolHQcHZ9anIkfc7JfVVDpQKN1CmPF0Ed3u4gIY6YMbj+uhzHf7su19SpThP8r5cLjhrl34dBHhVGEZe/E33vG7hpvk+zE6ZO21A46utoBLJdF7TAjrg+cspJ/B49eg2euK08sm+4X7MNvx4s', 
        ),
        'security' => array (
            'authnRequestsSigned' => false,
            'wantAssertionsSigned' => false,
        )

    );