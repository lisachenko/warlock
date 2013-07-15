<?php

namespace Warlock\Aspect;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Pointcut;
use Go\Lang\Annotation\After;
use Warlock\MarkerInterface\ObserverInterface;
use Warlock\MarkerInterface\SubjectInterface;

/**
 * Abstract observer aspect defines Observer pattern in AOP
 *
 * @see http://www.cs.ubc.ca/labs/spl/papers/2002/oopsla02-patterns.pdf
 */
abstract class AbstractObserverAspect implements Aspect
{

    /**
     * List of observers for subjects
     *
     * @var \SplObjectStorage|\SplObjectStorage[]
     */
    private $perSubjectObservers = null;

    /**
     * This pointcuts defines the concrete pointcut in the source code
     *
     * @Pointcut
     */
    abstract protected function subjectChange();

    /**
     * This method defines the concrete logic to be performed on observer
     *
     * @param SubjectInterface $subject Instance of subject
     * @param ObserverInterface $observer Instance of observer
     *
     * @return void
     */
    abstract protected function updateObserver(SubjectInterface $subject, ObserverInterface $observer);

    /**
     * Returns list of observers for subject
     *
     * @param SubjectInterface $s
     *
     * @return \SplObjectStorage|ObserverInterface[]
     */
    protected function getObservers(SubjectInterface $s)
    {
        if ($this->perSubjectObservers === null) {
            $this->perSubjectObservers = new \SplObjectStorage();
        }
        if (!isset($this->perSubjectObservers[$s])) {
            $observers = new \SplObjectStorage();
            $this->perSubjectObservers->offsetSet($s, $observers);
        } else {
            $observers = $this->perSubjectObservers->offsetGet($s);
        }
        return $observers;
    }

    /**
     * Add a specific observer on subject
     *
     * @param SubjectInterface $s
     * @param ObserverInterface $o
     */
    public function addObserver(SubjectInterface $s, ObserverInterface $o)
    {
        $this->getObservers($s)->attach($o);
    }

    /**
     * Remove a specific observer from subject
     *
     * @param SubjectInterface $subject
     * @param ObserverInterface $observer
     */
    public function removeObserver(SubjectInterface $subject, ObserverInterface $observer)
    {
        $this->getObservers($subject)->detach($observer);
    }

    /**
     * Logic to notify all observers on subject
     *
     * @After("$this->subjectChange")
     *
     * @param MethodInvocation $invocation
     */
    protected function afterSubjectChange(MethodInvocation $invocation)
    {
        $subject   = $invocation->getThis();
        $observers = $this->getObservers($subject);
        foreach ($observers as $observer) {
            $this->updateObserver($subject, $observer);
        }
    }
}
