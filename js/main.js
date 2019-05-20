/**
 * CERN IT-CDA-AD 01/02/2019
 * Piotr Jan Seweryn
 * CERNBox integration application for Gantt Chart Viewer
 * This software is covered by Affero General Public License version 3: ../license.txt
 *
 * This software was based on the work available at https://github.com/pawelrojek/nextcloud-drawio
 *
 **/

 (function (OCA) {

	OCA.Gantt = _.extend({}, OCA.Gantt);
	
	OCA.Gantt.Mimes = [];
	
	if (!OCA.Gantt.AppName) {
		OCA.Gantt = {
			AppName: "gantt"
		};
	}
	
	OCA.Gantt.DisplayError = function (error) {
		$("#app")
		.text(error)
		.addClass("error");
	};
	
	function Clean(receiver, filePath) {
		/* To restore default style */
		document.body.style.overflow = "auto";
		
		window.removeEventListener("message", receiver);
	
		var ncClient = OC.Files.getClient();
		ncClient.getFileInfo(filePath)
		.then(function (status, fileInfo) {
			var url = OC.generateUrl("/apps/files/?dir={currentDirectory}", {
				currentDirectory: fileInfo.path
			});
			window.location.href = url;
		})
		.fail(function () {
			var url = OC.generateUrl("/apps/files");
			window.location.href = url;
		});
	};
	
	function LoadFile(ganttChartApp, filePath) {
		
		var msg = OC.Notification.show(t(OCA.Gantt.AppName, "Receiving data, please wait..."));
	
		var ncClient = OC.Files.getClient();
	
		/* NOTE! getFileContents() from {core/js/files/client.js} and request() from {core/vendor/davclient.js/lib/client.js}
		   were modified in order to enable setting AJAX request response type to ARRAY BUFFER */
		ncClient.getFileContentsRT(filePath, null, (filePath.split('.').pop().toLowerCase() === "mpp") ? "arraybuffer" : "")
		.then(function(status, contents) {
			if(filePath.split('.').pop().toLowerCase() === "mpp") {
				var blob = new Blob([contents]);
				var reader = new FileReader();
				reader.onloadend = function() {
					ganttChartApp.postMessage(JSON.stringify({action: "TRANSFER", fileName: filePath, file: reader.result, exitCode: status}), "*");
				}
				reader.readAsDataURL(blob);
			}
			else {
				ganttChartApp.postMessage(JSON.stringify({action: "TRANSFER", fileName: filePath, file: contents, exitCode: status}),"*");
			}
		})
		.fail(function (status) {
			ganttChartApp.postMessage(JSON.stringify({action: "ERROR", message: "Connection status error: " + status, exitCode: status}),"*");
		})
		.done(function () {
			OC.Notification.hide(msg);
		});
	};
	
	function GetDirectoryContents(ganttChartApp, directoryPath, targetTreeViewId, targetTreeViewNode) {
		
		var ncClient = OC.Files.getClient();
	
		ncClient.getFolderContents(directoryPath)
		.then(function (status, filesAndDirs) {
			ganttChartApp.postMessage(JSON.stringify({action: "FILESANDDIRS", data: filesAndDirs, treeViewId: targetTreeViewId, treeViewNode: targetTreeViewNode, exitCode: status}),"*");
		})
		.fail(function (status) {
			ganttChartApp.postMessage(JSON.stringify({action: "ERROR", message: "Failed to get contents of the selected directory: " + directoryPath + "! Status: " + status, exitCode: status}),"*");
		});
	};
	
	OCA.Gantt.DataExchangeHandler = function (ganttChartApp, filePath, origin) {
	
		/* To remove additional/unwanted scrollbars */
		document.body.style.overflow = "hidden";
	
		var receiver = function (evt) {
		
			if(evt.data.length > 0 && origin.includes(evt.origin)) {
				
				var ganttChart = JSON.parse(evt.data);
				
				switch(ganttChart.request) {
					case "INIT":
						LoadFile(ganttChartApp, filePath);
						break;
					case "GETFILESANDDIRS":
						GetDirectoryContents(ganttChartApp, ganttChart.dirPath, ganttChart.treeViewId, ganttChart.treeViewNode);
						break;
					case "LOAD":
						LoadFile(ganttChartApp, ganttChart.loadFilePath);
						break;
					case "EXIT":
						Clean(receiver, filePath);
						break;
					default:
						ganttChartApp.postMessage(JSON.stringify({action: "ERROR", message: "CERNBox integration app: Unknown event was received!", exitCode: -1}),"*");
						console.log("CERNBox integration app: Unknown event was received!");
						console.log(ganttChart);
						break;
				}
			} else {
				ganttChartApp.postMessage(JSON.stringify({action: "ERROR", message: "CERNBox integration app: Unknown origin: (" + evt.origin + ") or empty message received", exitCode: -1}),"*");
				console.log("CERNBox integration app: Unknown origin: (" + evt.origin + ") or empty message received");
			}
		}
		window.addEventListener("message", receiver);
	};
	
	/* Action handler for opening files in Gantt Chart Viewer */
	OCA.Gantt.EditFileNewWindow = function (filePath) {
		var iframeTemplate = '<iframe id="iframeEditor" name="iframeEditor" allowfullscreen="true"></iframe>';
		
		$("#content").html(iframeTemplate);
		
		var iframe = $("#iframeEditor")[0];
		var ganttUrl = OCA.Gantt.Settings["viewer-server"] + "?username=" + OC.getCurrentUser().uid;
		var originUrl = OCA.Gantt.Settings["viewer-server"];
		console.log(OCA.Gantt.Settings);
		
		OCA.Gantt.DataExchangeHandler(iframe.contentWindow, filePath, originUrl);
		iframe.setAttribute('src', ganttUrl);
    }

	/* Set Gantt Chart Viewer as a default app for files with specific mime types */
	OCA.Gantt.FileList = {
		
		attach: function (fileList) {
			
			if (fileList.id == "trashbin") {
				return;
			}
	
			$.getJSON(OC.generateUrl("apps/" + OCA.Gantt.AppName + "/config"))
			.done(function (response) {
				OCA.Gantt.Settings = response;
				
				OCA.Gantt.Mimes = response.formats;
				
				$.each(OCA.Gantt.Mimes, function (ext, attr) {
					
					fileList.fileActions.registerAction({
						name: "ganttOpen",
						displayName: t(OCA.Gantt.AppName, "Open in GCV"),
						mime: attr.mime,
						permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE,
						icon: function () {
							return OC.imagePath(OCA.Gantt.AppName, "btn-edit");
						},
						iconClass: "icon-gantt",
						actionHandler: function (fileName, context) {
							var dir = fileList.getCurrentDirectory();
							OCA.Gantt.EditFileNewWindow(OC.joinPaths(dir, fileName));
						}
					});
					
					fileList.fileActions.setDefault(attr.mime, "ganttOpen");
					
				});
			})
			.fail(function () {
				/* Notify user of error */
				console.log("ERROR! Failed to retrieve compatible mime types");
			});
			
		}
	};
	
})(OCA);

OC.Plugins.register("OCA.Files.FileList", OCA.Gantt.FileList);

/* Change icons of files compatible with Gantt Chart Viewer in the main files list view */
$(document).ready(function () {
	ChangeIcons = function (mimetypes) {
		$("#filestable").find("tr[data-type=file]").each(function () {
			if (mimetypes.indexOf($(this).attr("data-mime")) >= 0 && $(this).find("div.thumbnail").length > 0) {
				if ($(this).find("div.thumbnail").hasClass("icon-gantt") == false) {
					$(this).find("div.thumbnail").addClass("icon icon-gantt");
				}
			}
		});
	};
		
	$.getJSON(OC.generateUrl("apps/" + OCA.Gantt.AppName + "/config"))
	.done(function(response) {
		OCA.Gantt.Settings = response;

		var mimetypes = [];
		response.formats.forEach(function(obj) {
			mimetypes.push(obj.mime);
		});

		if ($('#filesApp').val())
		{
			$('#app-content-files').add('#app-content-extstoragemounts')
			.on('changeDirectory', function (e) {
				ChangeIcons(mimetypes);
			})
			.on('fileActionsReady', function (e) {
				ChangeIcons(mimetypes);
			});
		}
			
	})
	.fail(function () {
		/* Notify user of error */
		console.log("ERROR! Failed to retrieve compatible mime types");
	});

	
});
