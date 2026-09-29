<?php

namespace GeneratorTest;

use EDI\Encoder;
use EDI\Generator\Interchange;
use EDI\Generator\Report;
use PHPUnit\Framework\TestCase;

/**
 * Class ReportTest
 * @package GeneratorTest
 */
class ReportTest extends TestCase
{
    private function encode(Report $report): string
    {
        $interchange = (new Interchange('sender', 'receiver'))
            ->setCharset('UNOC', '1')
            ->addMessage($report->compose());

        return (new Encoder($interchange->getComposed(), false))->get();
    }

    private function report(): Report
    {
        return (new Report('1'))
            ->setReference('JOY850000000120160526')
            ->setReason('POD', 'CFM')
            ->setDTM(new \DateTime('2026-09-29 10:00'), 'DSJ')
            ->setComment('Remarks');
    }

    public function testContactIsAfterDtmAndBeforeTxt()
    {
        $message = $this->encode($this->report()->setContact('Jean Dupont'));

        $this->assertStringContainsString("DTM+DSJ+260929+1000'CTA+Jean Dupont'TXT+DEL+Remarks'", $message);
    }

    public function testContactIsTruncatedTo35Characters()
    {
        $message = $this->encode($this->report()->setContact(str_repeat('é', 40)));

        $this->assertStringContainsString("CTA+" . str_repeat('é', 35) . "'", $message);
    }

    public function testNoContact()
    {
        $this->assertStringNotContainsString('CTA', $this->encode($this->report()));
        $this->assertStringNotContainsString('CTA', $this->encode($this->report()->setContact('  ')));
    }
}
