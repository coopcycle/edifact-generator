<?php

namespace EDI\Generator;

use DateTime;
use EDI\Generator\Segment\NameAndAddress;
use parent;


/**
 * Class Report
 * @package EDI\Generator
 */
class Report extends Message
{


    private $nad = [];
    private $ref = null;
    private $reason = null;
    private $dtm = [];
    private $comment = null;
    private $pod = [];
    private $receipt = null;

    /**
     * Construct.
     *
     * @param mixed $sMessageReferenceNumber (0062)
     * @param string $sMessageType (0065)
     * @param string $sMessageVersionNumber (0052)
     * @param string $sMessageReleaseNumber (0054)
     * @param string $sMessageControllingAgencyCoded (0051)
     * @param string $sAssociationAssignedCode (0057)
     */
    public function __construct(
        $sMessageReferenceNumber = null,
        $sMessageType = 'REPORT',
        $sMessageVersionNumber = '3',
        $sMessageReleaseNumber = '1',
        $sMessageControllingAgencyCoded = 'GT',
        $sAssociationAssignedCode = 'GTF'
    ) {
        parent::__construct(
            $sMessageType,
            $sMessageVersionNumber,
            $sMessageReleaseNumber,
            $sMessageControllingAgencyCoded,
            $sMessageReferenceNumber,
            $sAssociationAssignedCode
        );

        $this->setDTM(new DateTime(), 'DSJ');
    }

    public function setReference(?string $sRef): self
    {
        $this->ref = ['RFF', 'UNC', $sRef];
        return $this;
    }

    public function setReason(string $situation, string $justification, ?string $location = null, ?int $unit = null): self
    {
        $this->reason = ['RSJ', 'MS', $situation, $justification];
        return $this;
    }

    public function setDTM(\DateTime $datetime, string $qual = 'DDI'): self
    {
        $this->dtm[] = ['DTM', $qual, $datetime->format('ymd'), $datetime->format('Hi')];
        return $this;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = ['TXT', 'DEL', $comment];
        return $this;
    }


    public function addPOD(string $url): self
    {
        $this->pod[] = ['COM', $url, 'FT'];
        return $this;
    }

    /**
     * @param array<string> $urls
     */
    public function setPOD(array $urls): self
    {
        $this->pod = array_map(fn ($url) => ['COM', $url, 'FT'], $urls);
        return $this;
    }

    public function setReceipt(?string $receipt): self
    {
        $this->receipt = ['DOC', 'WBL', $receipt];
        return $this;
    }

    public function addNAD(NameAndAddress $nad): self
    {
        $this->nad[] = $nad;
        return $this;
    }

    /**
     * Compose.
     *
     * @param mixed $sMessageFunctionCode (1225)
     * @param mixed $sDocumentNameCode (1001)
     * @param mixed $sDocumentIdentifier (1004)
     *
     * @return \EDI\Generator\Message ::compose()
     * @throws \EDI\Generator\EdifactException
     */
    public function compose(?string $sMessageFunctionCode = null, ?string $sDocumentNameCode = null, ?string $sDocumentIdentifier = null): parent
    {
        $this->messageContent = [
            ['BGM', '', $this->messageID, ''],
        ];

        foreach ($this->nad as $nad) {
            $nad->compose();
            $this->messageContent[] = $nad->getComposed();
        }

        $this->messageContent[] = ['UNS', 'D'];

        // Set reference
        if (is_null($this->ref)) {
            throw new EdifactException('Reference is mandatory');
        }
        $this->messageContent[] = $this->ref;

        // Set reason
        if (is_null($this->reason)) {
            throw new EdifactException('Reason is mandatory');
        }
        $this->messageContent[] = $this->reason;


        // Set dtm
        foreach ($this->dtm as $dtm) {
            $this->messageContent[] = $dtm;
        }

        if (!is_null($this->comment)) {
            $this->messageContent[] = $this->comment;
        }

        foreach ($this->pod as $pod) {
            $this->messageContent[] = $pod;
        }

        if (!is_null($this->receipt)) {
            $this->messageContent[] = $this->receipt;
        }

        $this->messageContent[] = ['UNS', 'S'];

        return parent::compose();
    }
}
