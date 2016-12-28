<?php
namespace b166er;

class User extends Part
{
    public function fill()
    {
        $this->add(new DatetimeField('password_expiry', Field::SQL_NO_ATTRIBUTE, 128));
    }
}