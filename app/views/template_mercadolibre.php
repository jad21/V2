<?php $base_url_public = url("/")."/public/"; ?>

<link rel="stylesheet" type="text/css" href="<?=$base_url_public?>vendor/ng-ckeditor/ng-ckeditor.css">

<div class="container">
	<div class="row" >
		<div class="col-md-10 col-md-offset-1 center">
			<div class="panel panel-info" block-ui="blockForm">
				<div class="panel-heading">
					<h3 class="panel-title">Plantilla de mercado libre</h3>
				</div>
				<div class="panel-body">
					<form  class="form-horizontal">
						<textarea ckeditor="ctrl.editorOptions" ng-model="ctrl.editorValue"></textarea>
					</form>  
				</div>
				<div class="panel-footer clearfix">
					<div class="pull-right">
						<button class="btn btn-success" name="submit" ng-click="ctrl.save(ctrl.editorValue)"> Guardar </button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

