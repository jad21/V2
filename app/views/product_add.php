<link rel="stylesheet" type="text/css" href="public/vendor/jstree/style.min.css">

<div class="container">
	<div class="row">
		<div class="col-md-9 col-md-offset-1 center">
			<div class="panel panel-info" block-ui="blockForm">
				<div class="panel-heading">
					<h3 class="panel-title">Busqueda de productos</h3>
				</div>
				<div class="panel-body">
					<form  class="form-horizontal">
						<div class="form-group ">
							<label class="col-sm-2   control-label " for="select">
							Tipo de producto
							</label>
							<div class="col-sm-8">
								
								<select placeholder="seleccione..." class="select form-control" id="select" name="select" ng-model="ctrl.formData.tipo_producto">
									<option ng-repeat="t in ctrl.arrayTiposProductos" value="{{t.name}}" ng-disabled='(t.name!="simple" && t.name!="configurable")' trans="t.label">{{t.label}}</option>
								</select>
							</div>
						</div>
						<div class="form-group ">
							<label class="col-sm-2  control-label " for="select1">
							Busqueda
							</label>
							<div class="col-sm-8">
								<select class="select form-control" id="select1" name="select1" ng-model="ctrl.formData.selectedSearchtype" ng-options="t.id as t.des for t in ctrl.searchTypes">
								</select>
							</div>
						</div>
						<div class="ng-hide" ng-show="ctrl.formData.selectedSearchtype==2">
							<div class="form-group ">
								<label class="col-sm-2  control-label " for="select1">
								Indice de busqueda
								</label>
								<div class="col-sm-8">
									<select class="form-control" ng-model="ctrl.formData.search_indice">
										<option value="{{t}}" ng-repeat="t in ctrl.arraySearchIndices" trans="t"> </option>
									</select>
								</div>
							</div>
							
							<div class="form-group ">
								<label class="col-sm-2 control-label " for="textarea">
								Palabra Clave
								</label>
								<div class="col-sm-8">
									<textarea class="form-control" id="textarea" name="textarea" rows="3" ng-model="ctrl.formData.keywords"></textarea>
								</div>
							</div>
						</div>

						<div class="ng-hide" ng-show="ctrl.formData.selectedSearchtype==1">
							<div class="form-group ">
								<label class="col-sm-2  control-label " for="select1">
								Tipo de ID
								</label>
								<div class="col-sm-8">
									<select class="select form-control" id="select1" name="select1" ng-model="ctrl.formData.IdType">
										<?php foreach($tipo_items_id as $t): ?>
											<option value="<?=$t?>"> <?=$t?> </option>
										<?php endforeach ?>
									</select>
								</div>
							</div>
							<div class="form-group ">
								<label class="col-sm-2 control-label " for="text"> ID </label>
								<div class="col-sm-8">
									<input class="form-control" id="text" name="text" type="text" ng-model="ctrl.formData.itemId"/>
								</div>
							</div>
						</div>
					</form>  
				</div>
				<div class="panel-footer clearfix">
					<div class="pull-right">
						<button class="btn btn-info" name="submit" ng-disable="ctrl.condicionSearch()" ng-click="ctrl.search()"> Buscar </button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<div class="container">
	<div class="row">
		<section class="content">
			<div class="col-md-9 col-md-offset-1">
				<div class="panel panel-default">
					<div class="panel-body">
						<div class="pull-left">
							<div class="btn-group">
								<!-- <button type="button" class="btn btn-success btn-filter" data-target="guardado">Guardar Seleccion</button> -->
								<!-- <button type="button" class="btn btn-default btn-filter" >Guardar Todos</button> -->
								<button type="button" class="btn btn-default btn-filter" ng-click="ctrl.guardarSeleccion()">Guardar Seleccion</button>
								
								<!-- <select placeholder="Accion" class="select form-control" name="select" >
									<option></option>
									<option ng-click="ctrl.guardarSeleccion()">Guardar Seleccion</option>
									<option>Guardar Todos</option>
								</select> -->
							</div>
						</div>
						
						<div class="pull-right">
							<div class="btn-group">
								<button type="button" class="btn btn-success btn-filter" data-target="saved">Guardado</button>
								<button type="button" class="btn btn-warning btn-filter" data-target="pending">Pendiente</button>
								<!-- <button type="button" class="btn btn-danger btn-filter" data-target="cancelado">Cancelado</button> -->
								<button type="button" class="btn btn-info btn-filter" data-target="searched">Resultado</button>
								<button type="button" class="btn btn-default btn-filter" data-target="all">Todos</button>
							</div>
						</div>
						<div class="table-container">
							<table class="table table-filter">
								<tbody>
									<tr class="tr-table-filter" ng-repeat="p in ctrl.list_products" data-status="{{ctrl.condicionStatus(p)}}" >
										<?php require("partial-td-table-filter.php"); ?>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="content-footer">
					<p>
						Yaxa.co © - 2016 <br>
					</p>
				</div>
			</div>
		</section>
	</div>
</div>	
