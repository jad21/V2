<td class="td-table-filter">
	<div class="ckbox" ng-hide="p.checked_hide">
		<input type="checkbox" id="checkbox-{{p.ASIN}}" ng-disabled="p.disabled" ng-click="ctrl.checkbox(p)" ng-model="p.checked">
		<label for="checkbox-{{p.ASIN}}"></label>
	</div>
</td>
<td class="td-table-filter">
	<img class="img-responsive" ng-src="{{p.MediumImage.URL}}" alt="...">
</td>
<td class="td-table-filter" block-ui="blockP-{{p.ASIN}}">
	<div class="">
		<h4 class="title">
			<span lang="p.ItemAttributes.Title">{{p.ItemAttributes.Title}}</span>
			<span class="pull-right guardado status" ng-if="p.status=='saved'">
				(guardado)
				<!-- <a ng-if="p.product_url" href="{{p.product_url}}" target="_blank">ver aqui</a> -->
			</span>
			<span class="pull-right pendiente status" ng-if="p.status=='pending'">(pendiente)</span>
			<span class="pull-right cancelado status" ng-if="p.status=='cancelado'">
				<p ng-if="!p.msg_error"> (cancelado) </p>
				<p ng-if="p.msg_error" ng-bind="p.msg_error"> </p>
			</span>
		</h4>
		
		<div class="summary">
			<div class="">
				<ul id="myTabs" class="nav nav-tabs" role="tablist">
					<li role="presentation" class=""><a data-target="#ItemAttributes{{$index}}" role="tab" data-toggle="tab" aria-controls="home" aria-expanded="false">Detalles</a></li>
					<li ng-if="p.EditorialReviews.EditorialReview || p.ItemAttributes.Feature" role="presentation" class=""><a data-target="#EditorialReviews{{$index}}" role="tab"  data-toggle="tab" aria-expanded="true">Descripcion</a></li>
					<li ng-if="p.Variations" role="presentation" class=""><a data-target="#Variations{{$index}}" role="tab"  data-toggle="tab" aria-expanded="true">Variaciones</a></li>
					<li role="presentation"><a data-target="#CategoryMercadoLibre{{$index}}" role="tab"  data-toggle="tab" aria-expanded="true">Categoria En MercadoLibre</a></li>
					<li role="presentation" ng-if="p.product_url"><a data-target="#Links{{$index}}" role="tab"  data-toggle="tab" aria-expanded="true">Links</a></li>
					<li role="presentation" class=""><a data-target="#Hide{{$index}}" role="tab"  data-toggle="tab" aria-expanded="true">Ocultar</a></li>
				</ul>
				<div id="myTabContent" class="tab-content">
					<div role="tabpanel" class="tab-pane fade" id="ItemAttributes{{$index}}">
						<table class="table table-hover table-details">
							<tr>
								<td class="key text-right">Categorias:</td> 
								<td class="value text-left">
									<!-- <span ng-init="p.CategoryPath = ctrl.buildCategory(p.BrowseNodes)"></span> -->
									<ul>
										<li ng-repeat="(i,c) in p.CategoryPath">
											<span ng-repeat="category in c" lang="p.CategoryPath[i][$index]">
												{{category}} <span ng-if="!$last"> > </span>
											</span>
										</li>
									</ul>
								</td>
							</tr>
							<tr>
								<td class="key text-right">ASIN:</td> 
								<td class="value text-left" lang="p.ItemAttributes.Title">{{p.ASIN}}</td>
							</tr>
							<tr>
								<td class="key text-right">Titulo:</td> 
								<td class="value text-left" lang="p.ItemAttributes.Title">{{p.ItemAttributes.Title}}</td>
							</tr>
							
							<tr ng-if="p.OfferSummary.LowestNewPrice">
								<td class="key text-right">Precios:</td> 
								<td class="value text-left">
									{{p.Offers.Offer.OfferListing.Price.FormattedPrice || p.OfferSummary.LowestNewPrice.FormattedPrice}}
								</td>
							</tr>
							
							<!-- <tr ng-if="p.ItemAttributes.ItemDimensions" ng-repeat="(key, value) in p.ItemAttributes.ItemDimensions">
								<td class="key text-right"><span trans="key"></span></td> 
								<td class="value text-left">{{value | toCm}}</td>
							</tr> -->
							<tr ng-if="p.ItemAttributes.ItemDimensions" ng-init="obj = p.ItemAttributes.ItemDimensions" >
								<td class="key text-right">Dimensión del producto</td> 
								<td class="value text-left">
									<ul>
										<li ng-if="obj.Height"><b>Alto</b>:{{obj.Height | toCm}} cm</li>
										<li ng-if="obj.Length"><b>Largo</b>:{{obj.Length | toCm}} cm</li>
										<li ng-if="obj.Weight"><b>Peso</b>:{{obj.Weight}}</li>
										<li ng-if="obj.Width"><b>Ancho</b>:{{obj.Width | toCm}} cm</li>
									</ul>
								</td>
							</tr>
							
							<span ng-init='attrsArray=$keysAmazonWs.extras'></span>
							<tr ng-repeat="key in attrsArray" ng-if="p.ItemAttributes[key]">
								<td class="key text-right" ><span trans="key"></span>:</td> 
								<td class="value text-left" lang="p.ItemAttributes[key]">{{p.ItemAttributes[key]}}</td>
							</tr>

							<span ng-init='attrsArrayObligatorios=$keysAmazonWs.obligatorios'></span>
							<tr ng-repeat="key in attrsArrayObligatorios">
								<td class="key text-right" ><span trans="key"></span>:</td> 
								<td class="value text-left" ng-if="p.ItemAttributes[key]">{{p.ItemAttributes[key]}}</td>
								<td class="value text-left" ng-if="!p.ItemAttributes[key]">No tiene</td>
							</tr>
							
						</table>
					</div>
					<div ng-if="p.EditorialReviews || p.ItemAttributes.Feature" role="tabpanel" class="tab-pane fade" id="EditorialReviews{{$index}}">
						<p ng-bind-html="p.EditorialReviews.EditorialReview.Content" lang="p.EditorialReviews.EditorialReview.Content"></p>
						<div ng-if="p.ItemAttributes.Feature">
							<h4>Caracteristicas del producto</h4>
							<ul ng-if="p.ItemAttributes.Feature">
								<span lang="p.ItemAttributes.Feature"></span>
								<li ng-bind-html="f" ng-repeat="f in p.ItemAttributes.Feature track by $index"></li>
							</ul>
						</div>
					</div>

					<div ng-if="p.Variations" role="tabpanel" class="tab-pane fade" id="Variations{{$index}}">
						<div class="outer">
							<div class="inner">
								<table class="table-variations">
									<tr>
										<th>Variaciones</th>
										<td ng-repeat="i in p.Variations.Item" ng-if="i.Offers">
											<img ng-src="{{i.SmallImage.URL}}">
										</td>
									</tr>
									<tr ng-repeat="v in p.Variations.VariationDimensions.VariationDimension track by $index">
										<th trans="v">{{v}}</th>
										<td ng-repeat="i in p.Variations.Item" lang="i.ItemAttributes[v]" ng-if="i.Offers">{{i.ItemAttributes[v]}}</td>
									</tr>
									<tr >
										<th>ASIN</th>
										<td ng-repeat="i in p.Variations.Item" ng-if="i.Offers">
											<a href="javascript:void(0)" ng-click="ctrl.goByIdItem(i.ASIN)">{{i.ASIN}}</a>
										</td>
									</tr>
									<tr >
										<th>Precios</th>
										<td ng-repeat="i in p.Variations.Item" ng-if="i.Offers">{{i.Offers.Offer.OfferListing.Price.FormattedPrice}}
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
					<div id="Hide{{$index}}" role="tabpanel" class="tab-pane fade"></div>
					<div id="Links{{$index}}" ng-if="p.product_url" role="tabpanel" class="tab-pane fade">
						<div>
							<p>
								<b>links en magento: </b><a ng-if="p.product_url" href="{{p.product_url}}" target="_blank">ver aqui</a>
							</p>
						</div>
						<div ng-if="p.product_url_ml">
							<b>Links en mercado libre</b>
							<ul>
								<li ng-repeat="link in p.product_url_ml"><a href="{{link}}">Ver aqui</a></li>
							</ul>
						</div>
					</div>
					<div id="CategoryMercadoLibre{{$index}}" role="tabpanel" class="tab-pane fade">
						<h5>Seleccione una categoria para asignar el producto en mercado libre</h5>
						<div tree-category sku="{{p.ASIN}}"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</td>
