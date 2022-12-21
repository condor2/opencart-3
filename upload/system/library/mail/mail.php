<?php
/**
 * Basic PHP mail class
 */
namespace Mail;
class Mail {
    protected array $option = [];

    /**
     * Constructor
     *
     * @param    array  $option
     */
    public function __construct(array &$option = []) {
        $this->option = $option;
    }

    /**
     * Send
     *
     * @return    bool
     */
    public function send(): bool {
        if (is_array($this->option['to'])) {
            $to = implode(',', $this->option['to']);
        } else {
            $to = $this->option['to'];
        }

        if (version_compare(phpversion(), '8.2.0', '>=') || substr(PHP_OS, 0, 3) == 'WIN') {
            $eol = "\r\n";
        } else {
            $eol = PHP_EOL;
        }

        $boundary = '----=_NextPart_' . md5(time());

        $header  = 'MIME-Version: 1.0' . $eol;
        $header .= 'Date: ' . date('D, d M Y H:i:s O') . $eol;
        $header .= 'From: =?UTF-8?B?' . base64_encode($this->option['sender']) . '?= <' . $this->option['from'] . '>' . $eol;

        if (empty($this->option['reply_to'])) {
            $header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->option['sender']) . '?= <' . $this->option['from'] . '>' . $eol;
        } else {
            $header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->option['reply_to']) . '?= <' . $this->option['reply_to'] . '>' . $eol;
        }

        $header .= 'Return-Path: ' . $this->option['from'] . $eol;
        $header .= 'X-Mailer: PHP/' . phpversion() . $eol;
        $header .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . $eol . $eol;

        if (empty($this->option['html'])) {
            $message  = '--' . $boundary . $eol;
            $message .= 'Content-Type: text/plain; charset="utf-8"' . $eol;
            $message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
            $message .= base64_encode($this->option['text']) . $eol;
        } else {
            $message  = '--' . $boundary . $eol;
            $message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '_alt"' . $eol . $eol;
            $message .= '--' . $boundary . '_alt' . $eol;
            $message .= 'Content-Type: text/plain; charset="utf-8"' . $eol;
            $message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;

            if (!empty($this->option['text'])) {
                $message .= base64_encode($this->option['text']) . $eol;
            } else {
                $message .= base64_encode('This is a HTML email and your email client software does not support HTML email!') . $eol;
            }

            $message .= '--' . $boundary . '_alt' . $eol;
            $message .= 'Content-Type: text/html; charset="utf-8"' . $eol;
            $message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
            $message .= base64_encode($this->option['html']) . $eol;
            $message .= '--' . $boundary . '_alt--' . $eol;
        }

        if (!empty($this->option['attachments'])) {
            foreach ($this->option['attachments'] as $attachment) {
                if (is_file($attachment)) {
                    $handle = fopen($attachment, 'r');

                    $content = fread($handle, filesize($attachment));

                    fclose($handle);

                    $message .= '--' . $boundary . $eol;
                    $message .= 'Content-Type: application/octet-stream; name="' . basename($attachment) . '"' . $eol;
                    $message .= 'Content-Transfer-Encoding: base64' . $eol;
                    $message .= 'Content-Disposition: attachment; filename="' . basename($attachment) . '"' . $eol;
                    $message .= 'Content-ID: <' . urlencode(basename($attachment)) . '>' . $eol;
                    $message .= 'X-Attachment-Id: ' . urlencode(basename($attachment)) . $eol . $eol;
                    $message .= chunk_split(base64_encode($content));
                }
            }
        }

        $message .= '--' . $boundary . '--' . $eol;

        ini_set('sendmail_from', $this->option['from']);

        if (!empty($this->option['parameter'])) {
            return mail($to, '=?UTF-8?B?' . base64_encode($this->option['subject']) . '?=', $message, $header, $this->option['parameter']);
        } else {
            return mail($to, '=?UTF-8?B?' . base64_encode($this->option['subject']) . '?=', $message, $header);
        }
    }
}