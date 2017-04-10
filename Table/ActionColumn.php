<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\TableBundle\Table;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class ActionColumn extends AbstractColumn
{
    /**
     * @param OptionsResolver $resolver
     * @return mixed
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'items' => [],
            'showActionColumn' => []
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return '';
    }

    public function getTdClass()
    {
        return 'text-right';
    }

    /**
     * {@inheritdoc}
     */
    public function render($row)
    {
        $items = [];
        foreach ($this->options['items'] as $item) {
            if (array_key_exists($item['route'], $this->options['showActionColumn'])) {
                if (call_user_func($this->options['showActionColumn'][$item['route']], $row)) {
                    $items[] = $item;
                }
            } else {
                $items[] = $item;
            }
        }
        return $this->templating->render('whatwedoTableBundle::_actions.html.twig', [
            'row' => $row,
            'items' => $items
        ]);
    }
}
