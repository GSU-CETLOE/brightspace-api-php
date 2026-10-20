<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\User\Model;

use Brightspace\Api\Tests\Support\BrightspaceTestCase;
use Brightspace\Api\User\Model\User;
use Brightspace\Api\User\Model\UserActivation;

final class UserTest extends BrightspaceTestCase
{
    public function testCreateMapsEveryFieldBrightspaceSends(): void
    {
        $user = User::create(self::userData());

        self::assertSame(6606, $user->OrgId);
        self::assertSame(42, $user->UserId);
        self::assertSame('0042', $user->OrgDefinedId);
        self::assertSame('Jane', $user->FirstName);
        self::assertNull($user->MiddleName);
        self::assertSame('Doe', $user->LastName);
        self::assertSame('jdoe', $user->UserName);
        self::assertSame('jdoe@example.edu', $user->ExternalEmail);
        self::assertSame('jdoe', $user->UniqueIdentifier);
        self::assertTrue($user->Activation->IsActive);
        self::assertSame('Jane Doe', $user->DisplayName);
        self::assertInstanceOf(\DateTimeInterface::class, $user->LastAccessedDate);
        self::assertSame('2026-01-02T03:04:05+00:00', $user->LastAccessedDate->format(DATE_ATOM));
        self::assertSame('she/her', $user->Pronouns);
    }


    public function testCreateLeavesOptionalFieldsNullWhenTheyAreMissing(): void
    {
        $user = User::create([
            ...self::userData(),
            'OrgDefinedId' => null,
            'ExternalEmail' => null,
            'LastAccessedDate' => null,
        ]);

        self::assertNull($user->OrgDefinedId);
        self::assertNull($user->ExternalEmail);
        self::assertNull($user->LastAccessedDate);
    }


    public function testCreateAcceptsAUserObjectAndKeepsItsLastAccessedDate(): void
    {
        $original = User::create(self::userData());

        $copy = User::create($original);

        self::assertNotSame($original, $copy);
        self::assertSame(42, $copy->UserId);
        self::assertSame($original->LastAccessedDate, $copy->LastAccessedDate);
    }


    public function testDefaultsToAnActiveEmptyUser(): void
    {
        $user = new User();

        self::assertSame(0, $user->UserId);
        self::assertSame('', $user->UserName);
        self::assertTrue($user->Activation->IsActive);
        self::assertSame(0, $user->RoleId);
        self::assertFalse($user->SendCreationEmail);
    }


    public function testCreatePayloadCarriesTheRoleAndCreationFlags(): void
    {
        $user = new User(
            OrgDefinedId: '0042',
            FirstName: 'Jane',
            MiddleName: 'Q',
            LastName: 'Doe',
            UserName: 'jdoe',
            ExternalEmail: 'jdoe@example.edu',
            Activation: new UserActivation(false),
            Pronouns: 'she/her',
            RoleId: 110,
            SendCreationEmail: true
        );

        self::assertSame([
            'OrgDefinedId' => '0042',
            'FirstName' => 'Jane',
            'MiddleName' => 'Q',
            'LastName' => 'Doe',
            'UserName' => 'jdoe',
            'ExternalEmail' => 'jdoe@example.edu',
            'Pronouns' => 'she/her',
            'IsActive' => false,
            'RoleId' => 110,
            'SendCreationEmail' => true,
        ], $user->getCreatePayload());
    }


    public function testUpdatePayloadCarriesTheActivationBlock(): void
    {
        $user = User::create(self::userData());

        $payload = $user->getUpdatePayload();

        self::assertSame(
            [
                'OrgDefinedId',
                'FirstName',
                'MiddleName',
                'LastName',
                'UserName',
                'ExternalEmail',
                'Pronouns',
                'Activation',
            ],
            array_keys($payload)
        );
        self::assertSame($user->Activation, $payload['Activation']);
        self::assertSame('{"IsActive":true}', json_encode($payload['Activation']));
    }
}
