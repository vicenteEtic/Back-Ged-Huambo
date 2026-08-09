<?php

namespace Tests\Unit\RH\Declaration;

use App\Support\DeclarationText;
use App\Support\NumberToWordsPt;
use PHPUnit\Framework\TestCase;

class NumberToWordsPtTest extends TestCase
{
    public function test_converts_small_numbers(): void
    {
        $this->assertSame('um', NumberToWordsPt::toWords(1));
        $this->assertSame('dezanove', NumberToWordsPt::toWords(19));
        $this->assertSame('vinte e cinco', NumberToWordsPt::toWords(25));
        $this->assertSame('zero', NumberToWordsPt::toWords(0));
    }

    public function test_converts_hundreds(): void
    {
        $this->assertSame('cem', NumberToWordsPt::toWords(100));
        $this->assertSame('cento e um', NumberToWordsPt::toWords(101));
        $this->assertSame('cento e vinte e cinco', NumberToWordsPt::toWords(125));
        $this->assertSame('duzentos e cinquenta', NumberToWordsPt::toWords(250));
    }

    public function test_converts_thousands_with_e_connector(): void
    {
        $this->assertSame('mil', NumberToWordsPt::toWords(1000));
        $this->assertSame('mil e um', NumberToWordsPt::toWords(1001));
        $this->assertSame('mil e cem', NumberToWordsPt::toWords(1100));
        $this->assertSame('mil cento e vinte e cinco', NumberToWordsPt::toWords(1125));
        $this->assertSame('mil quinhentos e vinte e cinco', NumberToWordsPt::toWords(1525));
        $this->assertSame('dez mil', NumberToWordsPt::toWords(10000));
    }

    public function test_converts_millions(): void
    {
        $this->assertSame('um milhão', NumberToWordsPt::toWords(1000000));
        $this->assertSame('dois milhões', NumberToWordsPt::toWords(2000000));
        $this->assertSame('um milhão e cem mil', NumberToWordsPt::toWords(1100000));
        $this->assertSame('um milhão duzentos e cinquenta mil', NumberToWordsPt::toWords(1250000));
    }

    public function test_converts_money_to_words(): void
    {
        $this->assertSame('um kwanza', NumberToWordsPt::moneyToWords(1));
        $this->assertSame('um milhão duzentos e cinquenta mil kwanzas e cinquenta centavos', NumberToWordsPt::moneyToWords(1250000.50));
        $this->assertSame('zero kwanzas', NumberToWordsPt::moneyToWords(0));
    }

    public function test_date_longhand_and_sentence(): void
    {
        $this->assertSame('30 de Março de 2026', DeclarationText::dateLonghand('2026-03-30'));
        $this->assertSame('aos 30 de Março de 2026', DeclarationText::dateSentence('2026-03-30'));
    }

    public function test_gender_articles(): void
    {
        $this->assertSame('Senhor', DeclarationText::gender('masculino')['tratamento']);
        $this->assertSame('do', DeclarationText::gender('masculino')['artigo_do']);
        $this->assertSame('funcionário', DeclarationText::funcionario('masculino'));
        $this->assertSame('funcionária', DeclarationText::funcionario('feminino'));
        $this->assertSame('Senhora', DeclarationText::gender('feminino')['tratamento']);
    }
}
