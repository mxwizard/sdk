<?php

/*
 * The MIT License
 *
 * Copyright 2023 MX Wizard (https://mxwizard.net/).
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace vendor\mxwizard\sdk\src;

/**
 * Mailer (MX Wizard SDK)
 *
 * @author Sergey <sergey at mxwizard dot net>
 */
class Mailer {

    const UNKNOWN_ERROR = 1;
    const FILE_NOT_READABLE = 2;

    /**
     * The API URL.
     *
     * @var string
     */
    public $url = 'https://mxwizard.net/api/v1/mailer/send';

    /**
     * The maximum number of seconds to allow cURL functions to execute.
     *
     * @var int
     */
    public $timeout = 20;

    /**
     * The last error number.
     *
     * @var int
     */
    public $errorCode = 0;

    /**
     * The last error message.
     *
     * @var string
     */
    public $error = '';

    /**
     * The token ID.
     *
     * @var int
     */
    private $tokenId = 0;

    /**
     * The token value.
     *
     * @var string
     */
    private $token = '';

    /**
     * Request Data.
     *
     * @var array
     */
    private $data = [
        'from' => [
            'address' => '',
        ],
        'to' => [],
        'cc' => [],
        'bcc' => [],
        'subject' => '',
        'attachments' => [],
        'headers' => [],
    ];

    public function __construct(int $tokenId, string $token) {
        $this->tokenId = $tokenId;
        $this->token = $token;
    }

    /**
     * Set the "From" email address for the message.
     *
     * @param string $address
     * @param string $name
     *
     * @return Mailer
     */
    public function setFrom(string $address, string $name = null): self {
        $this->data['from']['address'] = trim($address);

        if (null !== $name) {
            $name = trim($name);
        }

        if ('' != $name) {
            $this->data['from']['name'] = $name;
        }

        return $this;
    }

    /**
     * Add a "To" address.
     *
     * @param string $address
     * @param string $name
     *
     * @return Mailer
     */
    public function addTo(string $address, string $name = null): self {
        $this->addAddress('to', $address, $name);

        return $this;
    }

    /**
     * Add a "Carbon copy" address.
     *
     * @param string $address
     * @param string $name
     *
     * @return Mailer
     */
    public function addCc(string $address, string $name = null): self {
        $this->addAddress('cc', $address, $name);

        return $this;
    }

    /**
     * Add a "Blind carbon copy" address.
     *
     * @param string $address
     * @param string $name
     *
     * @return Mailer
     */
    public function addBcc(string $address, string $name = null): self {
        $this->addAddress('bcc', $address, $name);

        return $this;
    }

    /**
     * Set the Subject of the message.
     *
     * @param string $subject
     *
     * @return Mailer
     */
    public function setSubject(string $subject): self {
        $this->data['subject'] = trim($subject);

        return $this;
    }

    /**
     * Set the HTML message
     *
     * @param string $html
     *
     * @return Mailer
     */
    public function setHtml(string $html): self {
        $this->data['html'] = $html;

        return $this;
    }

    /**
     * Set the plain-text message
     *
     * @param string $text
     *
     * @return Mailer
     */
    public function setText(string $text): self {
        $this->data['text'] = $text;

        return $this;
    }

    /**
     * Add an attachment from a path.
     *
     * @param string $path Path to the attachment file
     * @param string $filename Overrides the attachment name
     * @param string $type MIME type
     *
     * @return Mailer
     */
    public function addAttachment(string $path, string $filename = null, string $type = null): self {
        if (is_readable($path)) {
            if (null === $filename) {
                $filename = basename($path);
            }

            if (null === $type) {
                $type = mime_content_type($path);
            }

            $data = base64_encode(file_get_contents($path));

            $this->data['attachments'][] = [
                'filename' => $filename,
                'type' => $type ? $type : 'application/octet-stream',
                'data' => $data,
            ];
        } else {
            $this->errorCode = self::FILE_NOT_READABLE;
            $this->error = 'File "' . $path . '" is not readable';
        }

        return $this;
    }

    /**
     * Add a custom header.
     *
     * @param string $header
     *
     * @return Mailer
     */
    public function addCustomHeader(string $header): self {
        $this->data['headers'][] = $header;

        return $this;
    }

    /**
     * Send message.
     *
     * @return bool
     */
    public function send(): bool {
        $httpCode = 0;

        if (0 == $this->errorCode) {
            $ch = curl_init();

            curl_setopt_array($ch, $this->getCurlOptions());

            $response = json_decode(curl_exec($ch), true);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (200 != $httpCode) {
                $this->errorCode = $response['code'] ?? self::UNKNOWN_ERROR;
                $this->error = $response['message'] ?? 'Unknown error';
            }

            curl_close($ch);
        }

        return 200 == $httpCode;
    }

    /**
     * Get options for a cURL transfer.
     *
     * @return array
     */
    private function getCurlOptions(): array {
        return [
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => [
                'mxw-token-id: ' . $this->tokenId,
                'mxw-token: ' . $this->token,
            ],
            CURLOPT_POSTFIELDS => json_encode($this->data),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_URL => $this->url,
        ];
    }

    /**
     * Add an address (TO, CC, BCC).
     *
     * @param string $key
     * @param string $address
     * @param string $name
     */
    private function addAddress(string $key, string $address, string $name) {
        $item['address'] = trim($address);

        if (null !== $name) {
            $name = trim($name);
        }

        if ('' != $name) {
            $item['name'] = $name;
        }

        $this->data[$key][] = $item;
    }

}
