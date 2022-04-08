# OpenTHC Pub

This is a public server for registered users to publish and receive messages.

It's quite simple.
Register your public key and perhaps provide some information.
Other users can then publish to your inbox.


## Register A Public Key

Simply send an empty POST to your public-key as an enpoint.

```
curl -X POST https://openthc.pub/PUBLIC_KEY_eQWyDoW5ko7ECYfIUVbMIssxSe3On4M4
```


## Update Public Key Information

Create the *input.txt* file as encrypted data using your Secret Key and the public key of the server.
The contents of input.txt should be one large text string.
The base64 encoded Nonce, a colon ':' and then the base64 encoded Crypt data.

```
sodium-encrypt $SECRET $PUBLIC $SOURCE > input.txt
curl -X POST https://openthc.pub/PUBLIC_KEY_eQWyDoW5ko7ECYfIUVbMIssxSe3On4M4 --data @input.txt
```

To get the public key from the hosted server visit https://openthc.pub/pk

## Send a Message to a Public Key

Encrypt the data then POST from your PUBLIC_KEY to their PUBLIC_KEY/
The input data can be either a) text/plain or b) application/json or c) application/x-www-form-urlencoded.

```
curl -X POST https://openthc.pub/SOURCE_PUBLIC_KEY_eQWyDoW5ko7ECYfIUVbMIssxS/TARGET_PUBLIC_KEY_4htJi2A0yotK9sMPOBGecXbaB \
	--header 'content-type: application/json' \
	--data @input.txt
```


## Get Messages for a Public Key

```
curl https://openthc.pub/SOURCE_PUBLIC_KEY_eQWyDoW5ko7ECYfIUVbMIssxS

[
	"https://openthc.pub/SOURCE_PUBLIC_KEY_eQWyDoW5ko7ECYfIUVbMIssxS/MESSAGE_ID_91F5B6NVQ6D0CPH
	"https://openthc.pub/SOURCE_PUBLIC_KEY_eQWyDoW5ko7ECYfIUVbMIssxS/MESSAGE_ID_WMWG18E14WCQSG5
]
```



## Formats

Uploading messages supported in one of three formats.

#### *text/plain*

```
NONCE:CRYPT
```

#### *application/json*

```
{
	"nonce": "NONCE",
	"crypt": "CRYPT"
}
```

#### *application/x-www-form-urlencoded*

```
nonce=NONCE&crypt=CRYPT
```
