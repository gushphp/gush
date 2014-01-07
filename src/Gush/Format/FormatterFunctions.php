<?php

namespace Gush\Format;

function truncate($label)
{
    return substr($label, 0, 50).(strlen($label) > 50 ? '..' : '');
}