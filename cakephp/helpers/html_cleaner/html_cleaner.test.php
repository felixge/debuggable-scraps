<?php
App::Import('Helper', 'HtmlCleaner');
class HtmlCleanerTestCase extends UnitTestCase {
	function setUp() {
		$this->testObject = new HtmlCleanerHelper;
	}
	
	function testUnembeddableTags() {
		$input = '<p>some text here';
		$expected = '<p>some text here</p>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<p>some <p>text here';
		$expected = '<p>some </p><p>text here</p>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<p>some text here</p><p>';
		$expected = '<p>some text here</p><p></p>';
		$this->assertEqual($expected, $this->testObject->process($input));

		$input = '<a>some text here';
		$expected = '<a>some text here</a>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<a>some <a>text here';
		$expected = '<a>some </a><a>text here</a>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<a>some text here</a><a>';
		$expected = '<a>some text here</a><a></a>';
		$this->assertEqual($expected, $this->testObject->process($input));
	}

	function testSingleTags() {
		$input = '<img>some text here';
		$expected = '<img />some text here';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = 'some text here<br>';
		$expected = 'some text here<br />';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<br>some <br> <br> text <br> here<br><br>';
		$expected = '<br />some <br /> <br /> text <br /> here<br /><br />';
		$this->assertEqual($expected, $this->testObject->process($input));

		$input = '<br >some <br > <br > text <br > here<br ><br >';
		$expected = '<br />some <br /> <br /> text <br /> here<br /><br />';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<hr> <hr >some <hr > <hr > text <hr > here<hr ><hr >';
		$expected = '<hr /> <hr />some <hr /> <hr /> text <hr /> here<hr /><hr />';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<input >some <input > <input > text <input > here<input ><input >';
		$expected = '<input />some <input /> <input /> text <input /> here<input /><input />';
		$this->assertEqual($expected, $this->testObject->process($input));
	}
	
	function testEmbeddableTags() {
		$input = '<div>some text here';
		$expected = '<div>some text here</div>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<div>some <div>text here';
		$expected = '<div>some <div>text here</div></div>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<div>some text here</div><div>';
		$expected = '<div>some text here</div><div></div>';
		$this->assertEqual($expected, $this->testObject->process($input));


		$input = '<span>some text here';
		$expected = '<span>some text here</span>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<span>some <span>text here';
		$expected = '<span>some <span>text here</span></span>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<span>some text here</span><span>';
		$expected = '<span>some text here</span><span></span>';
		$this->assertEqual($expected, $this->testObject->process($input));


		$input = '<blockquote>some text here';
		$expected = '<blockquote>some text here</blockquote>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<blockquote>some <blockquote>text here';
		$expected = '<blockquote>some <blockquote>text here</blockquote></blockquote>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<blockquote>some text here</blockquote><blockquote>';
		$expected = '<blockquote>some text here</blockquote><blockquote></blockquote>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		
		$input = '<blockquote><span>some text here';
		$expected = '<blockquote><span>some text here</span></blockquote>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<blockquote>some <blockquote>text here<span>';
		$expected = '<blockquote>some <blockquote>text here<span></span></blockquote></blockquote>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<blockquote>some text here</span><blockquote>';
		$expected = '<blockquote>some text here<blockquote></blockquote></blockquote>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<blockquote>some <span>text</span2> here</span><blockquote>';
		$expected = '<blockquote>some <span>text here</span><blockquote></blockquote></blockquote>';
		$this->assertEqual($expected, $this->testObject->process($input));
	}
	
	function testAttributes() {
		$input = '<img src="testme">some text here';
		$expected = '<img src="testme"/>some text here';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = 'some text <div style="lala">here';
		$expected = 'some text <div style="lala">here</div>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = '<div cleanup="here" isFun="yes">';
		$expected = '<div cleanup="here" isFun="yes"></div>';
		$this->assertEqual($expected, $this->testObject->process($input));
	}
	
	function testUnclosedTagsAtEnd() {
		$input = 'some text here<div';
		$expected = 'some text here';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = 'some text here<div   ';
		$expected = 'some text here';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = 'some text here <span <span  ';
		$expected = 'some text here ';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = "some text here \n<span ";
		$expected = "some text here \n";
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = "some text here <div  \n></div><span ";
		$expected = "some text here \n>";
		$this->assertEqual($expected, $this->testObject->process($input));
	}
	
	function testReallyInvalidHtml() {
		$input = 'some </span><div>text<p> </div';
		$expected = 'some <div>text<p> </p></div>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = 'some </blockquote>text <div>here<div   <blockquote';
		$expected = 'some text <div>here</div>';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = 'some text here <d  ';
		$expected = 'some text here ';
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = 'some text here <spa';
		$expected = 'some text here ';
		$this->assertEqual($expected, $this->testObject->process($input));

	}
	
	function testComplex() {
		$input = <<<HTML
<p>For your convenience, I've also uploaded my slides to <a href="http://www.slideshare.net/the_undefined">my slideshare account</a>.</p><div
HTML;
		$expected = <<<HTML
<p>For your convenience, I've also uploaded my slides to <a href="http://www.slideshare.net/the_undefined">my slideshare account</a>.</p>
HTML;
		$this->assertEqual($expected, $this->testObject->process($input));
		
		$input = <<<HTML
<div class="body"><p>For your convenience, I've also uploaded my slides to <a href="http://www.slideshare.net/the_undefined">my slideshare account</a>.</p><div
HTML;
		$expected = <<<HTML
<div class="body"><p>For your convenience, I've also uploaded my slides to <a href="http://www.slideshare.net/the_undefined">my slideshare account</a>.</p></div>
HTML;
		$this->assertEqual($expected, $this->testObject->process($input));
	
	$input = <<<HTML
	<div class="body"><p>For your convenience, I've also uploaded my slides to <a href="http://www.slideshare.net/the_undefined">my slideshare account</a>.</p><div style="something here"
HTML;
			$expected = <<<HTML
	<div class="body"><p>For your convenience, I've also uploaded my slides to <a href="http://www.slideshare.net/the_undefined">my slideshare account</a>.</p></div>
HTML;
			$this->assertEqual($expected, $this->testObject->process($input));
	}
}
?>