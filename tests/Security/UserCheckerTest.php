<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    public function testDisabledAccountIsRejectedBeforeAuthentication(): void
    {
        try {
            (new UserChecker())->checkPreAuth((new User())->setIsActive(false));
            self::fail('Expected a CustomUserMessageAccountStatusException.');
        } catch (CustomUserMessageAccountStatusException $e) {
            self::assertSame(UserChecker::DISABLED_MESSAGE, $e->getMessageKey());
            self::assertStringContainsString('disabled by the administrator', $e->getMessageKey());
        }
    }

    public function testDisabledAccountIsRejectedAfterAuthenticationToo(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);

        (new UserChecker())->checkPostAuth((new User())->setIsActive(false));
    }

    public function testActiveAccountCanAuthenticate(): void
    {
        (new UserChecker())->checkPreAuth((new User())->setIsActive(true));

        self::assertTrue(true);
    }
}
