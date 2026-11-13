<?php

namespace App\Tests\Http;

use App\Http\QueryParam;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class QueryParamTest extends TestCase
{
    private function request(string $queryString): Request
    {
        return Request::create('/?'.$queryString);
    }

    public function testStringReturnsTrimmedScalarValue(): void
    {
        self::assertSame('hello', QueryParam::string($this->request('q=%20hello%20'), 'q'));
    }

    public function testStringFallsBackForMissingEmptyOrNonScalarValues(): void
    {
        self::assertSame('', QueryParam::string($this->request(''), 'q'));
        self::assertSame('all', QueryParam::string($this->request(''), 'filter', 'all'));
        self::assertSame('', QueryParam::string($this->request('filter[0]=x'), 'filter'));
        self::assertSame('all', QueryParam::string($this->request('filter[0]=x'), 'filter', 'all'));
    }

    public function testStringIsCappedAtMaxLength(): void
    {
        $long = str_repeat('a', 500);

        self::assertSame(255, mb_strlen(QueryParam::string($this->request('q='.$long), 'q')));
    }

    public function testIntReturnsValueOrNullForInvalidInput(): void
    {
        self::assertSame(4, QueryParam::int($this->request('staff=4'), 'staff'));
        self::assertSame(-1, QueryParam::int($this->request('staff=-1'), 'staff'));
        self::assertNull(QueryParam::int($this->request('staff='), 'staff'));
        self::assertNull(QueryParam::int($this->request('staff=abc'), 'staff'));
        self::assertNull(QueryParam::int($this->request('staff=1.5'), 'staff'));
        self::assertNull(QueryParam::int($this->request('staff[]=1'), 'staff'));
        self::assertNull(QueryParam::int($this->request(''), 'staff'));
    }
}
