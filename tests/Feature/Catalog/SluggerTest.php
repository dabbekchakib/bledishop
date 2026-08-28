<?php

namespace Tests\Feature\Catalog;

use App\Support\Slugger;
use Tests\TestCase;

class SluggerTest extends TestCase
{
    public function test_latin_slugs_are_lowercase_ascii(): void
    {
        $this->assertSame('smartphones-accessoires', Slugger::make('Smartphones & Accessoires !', 'fr'));
        $this->assertSame('produit-n2-eme-version', Slugger::make('Produit n°2 / éme version', 'en'));
        $this->assertSame('electronique', Slugger::make('Électronique', 'fr'));
    }

    public function test_arabic_slugs_keep_unicode_letters(): void
    {
        $this->assertSame('الهواتف-الذكية', Slugger::make('الهواتف الذكية', 'ar'));
        $this->assertSame('الإكسسوارات', Slugger::make('الإكسسوارات', 'ar'));
    }

    public function test_arabic_slugs_strip_diacritics(): void
    {
        $this->assertSame('الحواسيب', Slugger::make('الْحَوَاسِيب', 'ar'));
        $this->assertSame('ملابس', Slugger::make('مَلَابِس', 'ar'));
    }

    public function test_arabic_slugs_collapse_separators(): void
    {
        $this->assertSame('أجهزة-مكتبية', Slugger::make('أجهزة   -   مكتبية', 'ar'));
    }
}
