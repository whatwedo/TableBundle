<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
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

namespace whatwedo\TableBundle\Controller;


use Oepfelchasper\CoreBundle\Controller\CrudController;
use Symfony\Component\HttpFoundation\Request;
use whatwedo\TableBundle\Entity\Filter;
use whatwedo\TableBundle\Enum\FilterStateEnum;

class FilterController extends CrudController
{

    public function directCreateAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            $filter = new Filter();
            $filter->setName($request->request->get('filter_name'));
            $filter->setDescription($request->request->get('filter_description'));
            $filter->setState($request->request->getBoolean('filter_public') ? FilterStateEnum::ALL : FilterStateEnum::SELF);
            $filter->setCreatorUsername($this->getUser()->getUsername());
            $conditions = [];
            $conditions['filter_column'] = $request->request->get('filter_column', []);
            $conditions['filter_operator'] = $request->request->get('filter_operator', []);
            $conditions['filter_value'] = $request->request->get('filter_value', []);
            $filter->setConditions($conditions);
            $filter->setRoute($request->request->get('filter_route'));
            $filter->setArguments(json_decode($request->request->get('filter_route_arguments'), true));
            $filter->setConditions(json_decode($request->get('filter_conditions'), true));

            $em = $this->get('doctrine.orm.default_entity_manager');
            $em->persist($filter);
            $em->flush();

            return $this->redirectToFilter($filter);
        }

    }


    private function redirectToFilter(Filter $filter)
    {
        $path = $this->get('router')->generate(
            $filter->getRoute(),
            array_merge($filter->getArguments(), $filter->getConditions())
        );
        return $this->redirect($path);
    }

}