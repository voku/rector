<?php

namespace Rector\CodingStyle\Tests\Rector\FuncCall\ConsistentPregDelimiterRector\Fixture;

class SomeClass
{
    public function run()
    {
        preg_match('~value~', $value);
        preg_match_all('~value~im', $value);

        // ---

        $pattern = preg_quote('#~ŤÉŚŢ~#', '~');
        preg_match("~$pattern~u", $value);
        preg_match_all("~$pattern~u", $value);
    }
}

?>
  -----
<?php

namespace Rector\CodingStyle\Tests\Rector\FuncCall\ConsistentPregDelimiterRector\Fixture;

class SomeClass
{
    public function run()
    {
        preg_match('#value#', $value);
        preg_match_all('#value#im', $value);

        // ---

        $pattern = preg_quote('#~ŤÉŚŢ~#', '#');
        preg_match("#$pattern#u", $value);
        preg_match_all("#$pattern#u", $value);
    }
}

?>
