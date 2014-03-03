<?php

require 'wpdc/classes/class.DDWordPressDomainChanger.php';

class DDWordPressDomainChangerTest extends PHPUnit_Framework_TestCase
{
    public function testSerializedStrReplaceUnserialize()
    {
        $find_replace = array(
            "www.mysite.com"             => "www.example.com",
            "www.apple.com"              => "www.microsoft.com",
            "a.b.c.d.example.com"        => "www.example.com/a/b/c/d",
            "www.mysite.com/dan/doezema" => "dan.doezema.com",
            "staging.mysite.com"         => "www.mysite.com"
        );

        foreach ($find_replace as $find => $replace) {

            $array = array(
                "opt_brochure" => "http://$find/wp-content/uploads/2013/05/file1.pdf",
                "opt_contrat"  => "http://$find/wp-content/uploads/2013/05/file2.pdf",
                "opt_plan"     => "http://$find/wp-content/uploads/2013/05/file3.pdf",
                "opt_tarifs"   => "http://$find/wp-content/uploads/2013/05/file4.pdf",
                "opt_image"    => ""
            );

            $expected_array = array(
                "opt_brochure" => "http://$replace/wp-content/uploads/2013/05/file1.pdf",
                "opt_contrat"  => "http://$replace/wp-content/uploads/2013/05/file2.pdf",
                "opt_plan"     => "http://$replace/wp-content/uploads/2013/05/file3.pdf",
                "opt_tarifs"   => "http://$replace/wp-content/uploads/2013/05/file4.pdf",
                "opt_image"    => ""
            );


            $modified_serialize_array = DDWordPressDomainChanger::serializedStrReplace($find, $replace, serialize($array));

            $modified_array = unserialize($modified_serialize_array);

            $this->assertEquals($modified_array, $expected_array);
        }
    }
}