# OpenTHC Pub

This is a public server for registered users to publish and receive messages.

Endpoints are paths composed of a Public Keys and a Message Filename.
The Public Key is a URL Safe Base64 Encoded X25519 Public Key.

To send a Message use POST or PUT operation to a message endpoint like `$SERVER/ExW6uaFy79hvkndrGBtbuNvLuf0ztEGSM221bqr6yU0/MESSAGE.FILE`.

To register a Profile use POST or PUT operation to a profile endpoint like `$SERVER/qo-Kg2JWOQXosJvmJFmI2fFb_6rI8rz7fRLWz2OKuTY`.

----

## Message Operations

### Create Message

To create a message, from an authorized service, POST or PUT the message contents to an endpoint.
The contents may or may not be encrypted; that is a choice for the parties involved.

```
curl \
	-X POST \
	--header 'authorization: OpenTHC $MESSAGE_AUTH_BOX' \
	--header 'content-type: application/json' \
	--data '$MESSAGE_DATA' \
	$SERVER/$MESSAGE_PK/MESSAGE.FILE</pre>
```

Other files can be added into this message location.

```
curl \
	-X POST \
	--header 'authorization: OpenTHC $MESSAGE_AUTH_BOX' \
	--header 'content-type: application/json' \
	--data '$MESSAGE_DATA' \
	$SERVER/$MESSAGE_PK/MESSAGE2.FILE</pre>
```

### Update Message

You can update the message by posting an authorized update same path.

```
curl \
	-X POST \
	--header 'authorization: OpenTHC $MESSAGE_AUTH_BOX' \
	--header 'content-type: application/json' \
	--data '$MESSAGE_DATA' \
	$SERVER/$MESSAGE_PK/MESSAGE.FILE</pre>
```

----

## Profile Operations

A Profile is just a specially registered Public Key.
Register this endpoint by posting to the service key with the desired **profile-public-key**.
If you register with some Profile data, that will be saved.
Register your public key and perhaps provide some information.
Other users can then publish to your inbox.


```
curl \
	-X POST \
	--header 'authorization: OpenTHC $PROFILE_AUTH_BOX' \
	--header 'content-type: application/json' \
	--data '$PROFILE_JSON' \
	$SERVER/$PROFILE_PK
```


A profile is simply a public-key that is registered in the system.
Send a POST/PUT to your public-key with your profile information encrypted to the service public-key

```
curl $SERVER/$PK \
	-X POST \
	--header 'content-type: application/octet-stream' \
	--data '$JSON_BOX'
```


## Update A Profile

Create the *input.txt* file as encrypted data using your profile-secret-key and the service-public-key.
The contents of input.txt should be one large binary string.

```
sodium-encrypt $SECRET $PUBLIC $SOURCE > input.txt
curl -X POST $SERVER/$PROFILE_PK --data @input.txt
```


## Send a Message to a Profile

Just POST a message to a public key.
The message may or may not be encrypted.
The body is saved AS-IS and the indicated content-type is (mostly) trusted.
These end-points are write-once.
Only the public-key owner may fetch these documents.


Encrypt the data then POST from your PUBLIC_KEY to their PUBLIC_KEY/
The input data can be either a) text/plain or b) application/json or c) application/x-www-form-urlencoded.

The **profile-public-key**, if registered on this endpoint, will have the message stored in their incoming queue.

Message expire after 360 hours.


```
curl -X POST $SERVER/$PROFILE_PK/$MESSAGE_PK/$MESSAGE.FILE \
	--header 'content-type: application/json' \
	--data @input.txt
```

```
curl \
	-X POST \
	--header 'authorization: OpenTHC $SERVICE_AUTH_BOX' \
	--header 'content-type: $MESSAGE_TYPE' \
	--data '$MESSAGE_BODY' \
	$SERVER/$PROFILE_PK/$MESSAGE_PK/$MESSAGE.FILE
```

## Get Messages for a Public Key

<p>Simply call a `GET` request to your endpoint for a list of messages.</p>


```
curl
	--header 'authorization: OpenTHC $PROFILE_AUTH_BOX' \
	$SERVER/$PROFILE_PK
curl $SERVER/$PROFILE_PK

[
	"$SERVER/PUBLIC_KEY/MESSAGE_ID_91F5B6NVQ6D0CPH
	"$SERVER/PUBLIC_KEY/MESSAGE_ID_WMWG18E14WCQSG5
]
```

<p>Fetching a Single Message; which may need to be decrypted.</p>
<pre>curl $SERVER/$PROFILE_PK/$MESSAGE_PK/$MESSAGE.FILE</pre>


## Create Message

Create a new message-public-key (and keep the secret somewhere).
Then publish to this endpoint.

```
curl /$MESSAGE_PK/$MESSAGE.FILE \
	-X POST \
	--header 'authorization: $CLIENT_AUTH_BOX' \
	--header 'content-type: application/json' \
	--data '@file'
```

## Update Message

Update a message-public-key by writing and authorized request.
The CLIENT_AUTH_BOX should be extended to

```
SERVICE_ID.CONTACT_ID.COMPANY_ID.LICENSE_ID.MESSAGE_PK
```


```
curl /$MESSAGE-PK/$MESSAGE-ID \
	-X POST \
	--header 'authorization: $CLIENT_AUTH_BOX' \
	--header 'openthc-message-authorization: $MESSAGE_AUTH_BOX' \
	--header 'content-type: application/json' \
	--data '@file'
```

----

## Formats

Uploading messages supported in one of three formats.

- application/json
- application/octet-stream
- application/pdf
- text/plain
