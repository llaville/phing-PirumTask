<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */


/**
 * The <mapping> tag represents a complex data type.
 *
 * You can use nested <element> (and nested <element> with <element> tags)
 * to represent the full complexity of the structure.
 *
 * Example 1:
 * <code>
 * <mapping name="exceptions">
 *     <element key="LICENSE" value="doc" />
 *     <element key="templates/default/pirum.css" value="data" />
 *     <element key="templates/default/index.html" value="data" />
 * </mapping>
 * </code>
 *
 * Example 2:
 * <code>
 * <mapping name="deps">
 *     <element>
 *         <element key="channel" value="pear.php.net" />
 *         <element key="name" value="Console_CommandLine" />
 *         <element key="version" value="1.1.3" />
 *     </element>
 *     <element>
 *         <element key="channel" value="pear.pirum-project.org" />
 *         <element key="name" value="Pirum" />
 *         <element key="version" value="1.0.5" />
 *     </element>
 * </mapping>
 * </code>
 *
 * @author   Hans Lellelid <hans@xmpl.org>
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @package  phing.tasks.ext
 */
class Mapping extends DataType
{
    private $name;
    private $elements = array();

    public function setName($v)
    {
        $this->name = $v;
    }

    public function getName(Project $p)
    {
        if ($this->isReference()) {
            $obj = $this->getCheckedRef(get_class($this), 'mapping');
            return $obj->getName($p);
        }
        return $this->name;
    }

    public function createElement()
    {
        if ($this->isReference()) {
            throw $this->noChildrenAllowed();
        }
        $e = new MappingElement();
        $this->elements[] = $e;
        return $e;
    }

    public function getElements(Project $p)
    {
        if ($this->isReference()) {
            $obj = $this->getCheckedRef(get_class($this), 'mapping');
            return $obj->getElements($p);
        }
        return $this->elements;
    }

    /**
     * Returns the PHP hash or array of hashes (etc.) that this mapping represents.
     * @return array
     */
    public function getValue()
    {
        $value = array();
        foreach($this->getElements($this->project) as $el) {
            if ($el->getKey() !== null) {
                $value[ $el->getKey() ] = $el->getValue();
            } else {
                $value[] = $el->getValue();
            }
        }
        return $value;
    }
}

/**
 * Sub-element of <mapping>.
 *
 * @package  phing.tasks.ext
 */
class MappingElement
{
    private $key;
    private $value;
    private $elements = array();

    public function setKey($v)
    {
        $this->key = $v;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setValue($v)
    {
        $this->value = $v;
    }

    /**
     * Returns either the simple value or
     * the calculated value (array) of nested elements.
     * @return mixed
     */
    public function getValue()
    {
        if (!empty($this->elements)) {
            $value = array();
            foreach($this->elements as $el) {
                if ($el->getKey() !== null) {
                    $value[ $el->getKey() ] = $el->getValue();
                } else {
                    $value[] = $el->getValue();
                }
            }
            return $value;
        }
        return $this->value;
    }

    /**
     * Handles nested <element> tags.
     */
    public function createElement()
    {
        $e = new MappingElement();
        $this->elements[] = $e;
        return $e;
    }

}
