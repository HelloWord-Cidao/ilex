<?php

use \Ilex\Lib\Validate;

class ValidateTest extends PHPUnit_Framework_TestCase
{
    public function testValidate()
    {
        $data = [
            'password' => '1234',
            'email' => 'someone@some.site',
            'mobile' => '12001111222',
            'age' => '23',
            'code' => 'af3s!',
            'answer' => '42',
            'planets' => [
                'ab12', 'cd34', 'kfc'
            ],
            'flowers' => [
                'yz'
            ]
        ];
        $result = Validate::batch($data, [
            'username' => [
                'require' => ['message' => 'NAME_REQUIRED'],
                'length_gt' => ['value' => 0]
            ],
            'password' => [
                'require' => ['message' => 'PASSWORD_REQUIRED'],
                'length_ge' => ['value' => 6, 'message' => 'PASSWORD_LENGTH_LT_6']
            ],
            'email' => [
                're' => ['type' => 'email', 'message' => 'EMAIL_PATTERN_FAIL'],
                'default' => 'nobody@no.site'
            ],
            'mobile' => [
                're' => ['type' => 'mobile', 'message' => 'MOBILE_PATTERN_FAIL']
            ],
            'age' => [
                'require' => ['message' => 'AGE_REQUIRE'],
                'type' => ['type' => 'int', 'message' => 'AGE_TYPE_FAIL']
            ],
            [
                'name' => 'code',
                'require' => ['message' => 'CODE_REQUIRE'],
                're' => ['pattern' => '/^[0-9A-Za-z]{4}$/', 'message' => 'CODE_PATTERN_FAIL'],
                'length_eq' => ['value' => 4, 'message' => 'CODE_LENGTH_NE_4']
            ],
            [
                'name' => 'gender',
                'default' => 'male'
            ],
            [
                'name' => 'answer',
                'eq' => ['value' => 42, 'message' => 'ANSWER_WRONG']
            ],
            [
                'name' => 'planets',
                'type' => ['type' => 'array', 'message' => 'PLANETS_TYPE_FAIL'],
                'all' => [
                    're' => ['pattern' => '@^\w\w\d\d$@', 'message' => 'PLANETS_PATTERN_FAIL']
                ]
            ],
            [
                'name' => 'flowers',
                'type' => ['type' => 'array', 'message' => 'FLOWERS_TYPE_FAIL'],
                'all' => [
                    're' => ['pattern' => '@^\w+$@', 'message' => 'FLOWERS_PATTERN_FAIL']
                ]
            ]
        ));
        $this->assertSame([
            'username' => ['NAME_REQUIRED'],
            'password' => ['PASSWORD_LENGTH_LT_6'],
            'mobile' => ['MOBILE_PATTERN_FAIL'],
            'code' => ['CODE_PATTERN_FAIL', 'CODE_LENGTH_NE_4'],
            'planets' => ['PLANETS_PATTERN_FAIL']
        ], $result, 'Validation result does not come out as expected.');

        $this->assertSame(23, $data['age'], 'Validation\'s type requirement fails.');

        $this->assertArrayHasKey('gender', $data, 'Validation\'s default fails.');
        $this->assertSame('male', $data['gender'], 'Validation\'s default fails.');
    }
}