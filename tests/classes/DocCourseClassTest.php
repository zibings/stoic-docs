<?php

	use Zibings\DocCourse;
	use Zibings\DocLesson;
	use Zibings\DocLessonStep;
	use Zibings\DocPageModes;

	class DocCourseClassTest extends ZsfTestCase {
		public function test_Crud() : void {
			$v1   = DocTestHelper::makeVersion(self::$db, self::$log);
			$page = DocTestHelper::makePage(self::$db, self::$log, $v1, DocPageModes::LEARN);

			$course        = new DocCourse(self::$db, self::$log);
			$course->slug  = 'course-' . uniqid();
			$course->title = 'A course';
			self::assertTrue($course->create()->isGood());
			self::assertEquals($course->id, DocCourse::fromSlug($course->slug, self::$db, self::$log)->id);

			$dup        = new DocCourse(self::$db, self::$log);
			$dup->slug  = $course->slug;
			$dup->title = 'dup';
			self::assertFalse($dup->create()->isGood(), "duplicate slug must be rejected");

			$lesson           = new DocLesson(self::$db, self::$log);
			$lesson->courseId = $course->id;
			$lesson->pageId   = $page->id;
			$lesson->ordinal  = 1;
			self::assertTrue($lesson->create()->isGood());

			$step           = new DocLessonStep(self::$db, self::$log);
			$step->lessonId = $lesson->id;
			$step->ordinal  = 1;
			$step->prompt   = 'Do the thing';
			self::assertTrue($step->create()->isGood());

			$step->hint = 'A hint';
			self::assertTrue($step->update()->isGood());
			self::assertEquals('A hint', DocLessonStep::fromId($step->id, self::$db, self::$log)->hint);

			self::assertTrue($course->delete()->isGood());
			self::assertEquals(0, DocLesson::fromId($lesson->id, self::$db, self::$log)->id, "lessons cascade");
			self::assertEquals(0, DocLessonStep::fromId($step->id, self::$db, self::$log)->id, "steps cascade");

			$page->delete();
			$v1->delete();

			return;
		}
	}
