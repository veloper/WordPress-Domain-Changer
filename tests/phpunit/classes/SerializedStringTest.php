<?php
require_once '../unit_helper.php';
require_once CLASSES_PATH . '/class.SerializedString.php';

class SerializedStringTest extends PHPUnit_Framework_TestCase
{
    public function testReplaceMethod()
    {
        $find_replace = array(
            "www.mysite.com"             => "www.example.com",
            "www.apple.com"              => "www.microsoft.com",
            "a.b.c.d.example.com"        => "www.example.com/a/b/c/d",
            "www.mysite.com/dan/doezema" => "dan.doezema.com",
            "staging.mysite.com"         => "www.mysite.com"
        );

        foreach ($find_replace as $find => $replace) {

            $original = serialize(
                array(
                    "opt_brochure" => "http://$find/wp-content/uploads/2013/05/file1.pdf",
                    "opt_contrat"  => "http://$find/wp-content/uploads/2013/05/file2.pdf",
                    "opt_plan"     => "http://$find/wp-content/uploads/2013/05/file3.pdf",
                    "opt_tarifs"   => "http://$find/wp-content/uploads/2013/05/file4.pdf",
                    "opt_image"    => ""
                )
            );

            $expected = serialize(
                array(
                    "opt_brochure" => "http://$replace/wp-content/uploads/2013/05/file1.pdf",
                    "opt_contrat"  => "http://$replace/wp-content/uploads/2013/05/file2.pdf",
                    "opt_plan"     => "http://$replace/wp-content/uploads/2013/05/file3.pdf",
                    "opt_tarifs"   => "http://$replace/wp-content/uploads/2013/05/file4.pdf",
                    "opt_image"    => ""
                )
            );


            $modified = new SerializedString($original);
            $modified->replace($find, $replace);

            $this->assertEquals(unserialize($modified->toString()), unserialize($expected));
        }
    }
}