<?php
    $spBaseUrl = 'https://localhost/est2-test/saml'; //php-saml‚Ö‚ÌURL‚ğƒZƒbƒg
    $ldpBaseUrl = 'https://gohome292-dev-ed.my.salesforce.com';

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
            'x509cert' => 'MIIErDCCA5SgAwIBAgIOAXQ5ArLyAAAAAEbskZkwDQYJKoZIhvcNAQELBQAwgZAxKDAmBgNVBAMMH1NlbGZTaWduZWRDZXJ0XzI5QXVnMjAyMF8wNjU4MzIxGDAWBgNVBAsMDzAwRDJ3MDAwMDAzeVJlTDEXMBUGA1UECgwOU2FsZXNmb3JjZS5jb20xFjAUBgNVBAcMDVNhbiBGcmFuY2lzY28xCzAJBgNVBAgMAkNBMQwwCgYDVQQGEwNVU0EwHhcNMjAwODI5MDY1ODMyWhcNMjEwODI5MDAwMDAwWjCBkDEoMCYGA1UEAwwfU2VsZlNpZ25lZENlcnRfMjlBdWcyMDIwXzA2NTgzMjEYMBYGA1UECwwPMDBEMncwMDAwMDN5UmVMMRcwFQYDVQQKDA5TYWxlc2ZvcmNlLmNvbTEWMBQGA1UEBwwNU2FuIEZyYW5jaXNjbzELMAkGA1UECAwCQ0ExDDAKBgNVBAYTA1VTQTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAJeeCV5KYN7qEKfgKCZummZR0B65/TmCCv/gZgp1a7uAoIgJHDklXBskshZ90bxwR/uDUAjuimbmbA9Ugq1pHJhdJ/Imy6a3z1z5ontAM9L8DulPXHyQAoUK7RLWI3ctGMxTLxj5s6lpvEulWDGQQDJVKH6rT65gIgZl7Ql8iFiDFDv2E+jg26cPjzmtx0oE2UThX4TZn/SvLpkDps+CVoBLvS5aniGypzqdlw+ma1Ann/x00AV7GBpyaT2uGQqwSlb55Rxr9PapaUVRQAsEMOxmomt31aYICz/F+Ljf/Fy/kiR2lBSHAYhT9g9dfYMPjpR8CFqqVMpnQ2FhBgICMp0CAwEAAaOCAQAwgf0wHQYDVR0OBBYEFAVXkblpvvqRnSs1zTi7n8sw/A7JMA8GA1UdEwEB/wQFMAMBAf8wgcoGA1UdIwSBwjCBv4AUBVeRuWm++pGdKzXNOLufyzD8DsmhgZakgZMwgZAxKDAmBgNVBAMMH1NlbGZTaWduZWRDZXJ0XzI5QXVnMjAyMF8wNjU4MzIxGDAWBgNVBAsMDzAwRDJ3MDAwMDAzeVJlTDEXMBUGA1UECgwOU2FsZXNmb3JjZS5jb20xFjAUBgNVBAcMDVNhbiBGcmFuY2lzY28xCzAJBgNVBAgMAkNBMQwwCgYDVQQGEwNVU0GCDgF0OQKy8gAAAABG7JGZMA0GCSqGSIb3DQEBCwUAA4IBAQBOy1dzbelvQM5dpPSd6jrpsyuEoUl98Yhz3Af7dT6HlErxlXv3LS/s2iHyPDtacYOCDOXfvxY12u+bI9jJ3Fk2XN/siqU3UDH71ae8IMbm+/va4Kry0oepsgrf15XyifF1NvG3wOY1JN3J5dbNTJYEwn3B9QR/CfMaMz5N0SSujUMZ1O0jo+lZCS+GKzSHmEPxtQQghkHe8qeODCSsTBN/CAVB+FtgKsegqH9iDybt4wIGNm15J2ia6y8rc59VU3Vyt4A3MNvm2ILUeIaOv9u5B61V3a4hoekdGEA0NJlqHHOUIQ5fGS8GB+PtNKNG3HeQ9/1svCLxQolxiLrK6B+E',
        ),
        'security' => array (
            'authnRequestsSigned' => true,
            'wantAssertionsSigned' => true,
        )

    );