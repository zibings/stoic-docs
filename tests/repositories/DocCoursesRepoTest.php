<?php

	use Zibings\DocCourse;
	use Zibings\DocCourses;
	use Zibings\DocLesson;
	use Zibings\DocLessonStep;
	use Zibings\DocPageModes;

	class DocCoursesRepoTest extends ZsfTestCase {
		public function test_LessonsAndSteps() : void {
			$repo = new DocCourses(self::$db, self::$log);
			$v1   = DocTestHelper::makeVersion(self::$db, self::$log);
			$p1   = DocTestHelper::makePage(self::$db, self::$log, $v1, DocPageModes::LEARN);
			$p2   = DocTestHelper::makePage(self::$db, self::$log, $v1, DocPageModes::LEARN);

			$course        = new DocCourse(self::$db, self::$log);
			$course->slug  = 'course-' . uniqid();
			$course->title = 'Course';
			$course->create();

			foreach ([[$p2, 2], [$p1, 1]] as [$page, $ordinal]) {
				$lesson           = new DocLesson(self::$db, self::$log);
				$lesson->courseId = $course->id;
				$lesson->pageId   = $page->id;
				$lesson->ordinal  = $ordinal;
				$lesson->create();
			}

			$lessons = $repo->getLessons($course->id);
			self::assertCount(2, $lessons);
			self::assertEquals($p1->id, $lessons[0]['page']->id, "ordered by ordinal");
			self::assertEquals(1, $lessons[0]['lesson']->ordinal);

			$first = $lessons[0]['lesson'];

			foreach ([2, 1] as $ordinal) {
				$step           = new DocLessonStep(self::$db, self::$log);
				$step->lessonId = $first->id;
				$step->ordinal  = $ordinal;
				$step->prompt   = "Step {$ordinal}";
				$step->create();
			}

			$steps = $repo->getSteps($first->id);
			self::assertEquals([1, 2], array_map(fn ($s) => $s->ordinal, $steps));

			self::assertEquals($first->id, $repo->getLessonForPage($p1->id)->id);
			self::assertContains($course->id, array_map(fn ($c) => $c->id, $repo->getAll()));

			$course->delete();
			$p2->delete();
			$p1->delete();
			$v1->delete();

			return;
		}
	}
