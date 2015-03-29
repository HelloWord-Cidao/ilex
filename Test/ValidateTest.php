<?php


use \Ilex\Lib\Validate;


class ValidateTest extends PHPUnit_Framework_TestCase
{
    public function testValidate()
    {
        $data = array(
            'password' => '1234',
            'age' => '23',
            'code' => 'af3s!',
            'answer' => '42',
            'planets' => array(
                'ab12', 'cd34', 'kfc'
            ),
            'flowers' => array(
                'yz'
            )
        );
        $result = Validate::batch($data, array(
            array(
                'name' => 'username',
                'require' => array('message' => 'NAME_REQUIRED'),
                'length_gt' => array('value' => 0)
            ),
            array(
                'name' => 'password',
                'require' => array('message' => 'PASSWORD_REQUIRED'),
                'length_ge' => array('value' => 6, 'message' => 'PASSWORD_LENGTH_LT_6')
            ),
            array(
                'name' => 'age',
                'require' => array('message' => 'AGE_REQUIRE'),
                'type' => array('type' => 'int', 'message' => 'AGE_TYPE_FAIL')
            ),
            array(
                'name' => 'code',
                'require' => array('message' => 'CODE_REQUIRE'),
                're' => array('pattern' => '/^[0-9A-Za-z]{4}$/', 'message' => 'CODE_PATTERN_FAIL'),
                'length_eq' => array('value' => 4, 'message' => 'CODE_LENGTH_NE_4')
            ),
            array(
                'name' => 'gender',
                'default' => 'male'
            ),
            array(
                'name' => 'answer',
                'eq' => array('value' => 42, 'message' => 'ANSWER_WRONG')
            ),
            array(
                'name' => 'planets',
                'type' => array('type' => 'array', 'message' => 'PLANETS_TYPE_FAIL'),
                'all' => array(
                    're' => array('pattern' => '@^\w\w\d\d$@', 'message' => 'PLANETS_PATTERN_FAIL')
                )
            ),
            array(
                'name' => 'flowers',
                'type' => array('type' => 'array', 'message' => 'FLOWERS_TYPE_FAIL'),
                'all' => array(
                    're' => array('pattern' => '@^\w+$@', 'message' => 'FLOWERS_PATTERN_FAIL')
                )
            )
        ));
        $this->assertSame(array(
            'username' => array('NAME_REQUIRED'),
            'password' => array('PASSWORD_LENGTH_LT_6'),
            'code' => array('CODE_PATTERN_FAIL', 'CODE_LENGTH_NE_4'),
            'planets' => array('PLANETS_PATTERN_FAIL')
        ), $result, 'Validation result does not come out as expected.');

        $this->assertSame(23, $data['age'], 'Validation\'s type requirement fails.');

        $this->assertArrayHasKey('gender', $data, 'Validation\'s default fails.');
        $this->assertSame('male', $data['gender'], 'Validation\'s default fails.');
    }
}