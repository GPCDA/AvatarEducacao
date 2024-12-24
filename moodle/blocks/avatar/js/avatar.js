jQuery( document ).ready(function() {
	
	window.AvatarUI = jQuery.extend({}, {
		
		AvatarUIController : null,
		
		init : function(gender, avatarid, courseid, userid, visemsMessage, notificationPhrase, tempPrefix) {
			
			var self = this;
			
			AvatarUIController = self;

			self.tempFolder = M.cfg['wwwroot'] + '/blocks/avatar/engine/temp';
			
			self.courseid = courseid;
			self.userid = userid;
			self.visemsMessage = visemsMessage;
			self.notificationPhrase = notificationPhrase;
			self.speaker = gender == "male" ? 'cid' : 'lis';
			self.tempPrefix = tempPrefix;
			
			self.avatarContainer = jQuery("#avatar_container");
			jQuery("#avatar_canvas_tmpl").tmpl().appendTo(self.avatarContainer);
			
			self.audioConfig();
			
			self.avatarCanvas = jQuery("#avatar_canvas").get(0);
			self.avatarContext2D = self.avatarCanvas.getContext('2d');

			self.played = false;
			self.timeoutID = false;
			self.intervalID = false
			
			self.flagIE9 = self.ieHack();
			self.indice = 0;
			self.x = -17;
			self.y = 0;
			
			self.defineAvatarGenderFolder(gender, avatarid);
			
			self.cacheMerged = new Array();
			self.cacheMouths = new Array();

			self.arrayVisemas = new Array();
			self.arrayTempos = new Array();
			
			self.cacheAvatar();
			
			if(jQuery("#avatar_audio_controls li").length == 0) {
				jQuery("#avatar_audio_controls").hide();
			}
			
			self.bindEvents();
			
		},
		
		bindEvents : function() {
			
			var self = this;
			
			jQuery("#btn_message").click(function() {
				
				var parameter;
				
				if(self.courseid > 1) {
					parameter = 2;
				} else {
					parameter = 3;
				}
				
				self.playAnimation(parameter, self.userid, self.courseid);
				
				/*jQuery("#btn_message span").remove();*/
			});
			
			if(self.courseid > 1) {
			
				jQuery("#btn_notification").click(function() {
					self.playAnimation(1, self.userid, self.courseid);
					/*jQuery("#btn_notification span").remove();*/
				});
			}
			
		},
		
		audioConfig : function() {
			
			var self = this;
			
			jQuery("#avatar_audio_message_tmpl").tmpl().appendTo(self.avatarContainer);
			jQuery("#avatar_audio_notification_tmpl").tmpl().appendTo(self.avatarContainer);
			
			if(self.courseid > 1) {
				jQuery("#avatar_audio_message_src").attr("src", self.tempFolder + "/" + self.tempPrefix + "message_course" + self.courseid + ".mp3" + "?n=" + Math.floor(Math.random()*100000));
			} else {
				jQuery("#avatar_audio_message_src").attr("src", self.tempFolder + "/" + self.tempPrefix + "message_home.mp3" + "?n=" + Math.floor(Math.random()*100000));
			}
			
		},
		
		draw : function() {
			
			var self = this;
			
			var image = new Image();
		    var mouth = new Image();
		    
		    self.avatarContext2D.clearRect(0, 0, self.avatarCanvas.width, self.avatarCanvas.height);
		    
	    	/* se for true, = repouso, senão repouso_closed */
		    var flagMerged = true;

		    /* piscar olhos */
		    if((self.indice % 17) == 0 && self.indice != 0) {
		    	
		        image.src = M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/merged/repouso_closed.png';
		        flagMerged = false;
		        
		    } else {
		    	
		        image.src = M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/merged/repouso.png';
		        flagMerged = true;
		        
		    }

		    /* desenha a imagem de background */

		    if(!self.flagIE9) {
		    	
		        if(flagMerged) {
		        	
		            self.avatarContext2D.drawImage(self.cacheMerged[1], self.x, self.y);
		            
		        } else {
		        	
		            self.avatarContext2D.drawImage(self.cacheMerged[0], self.x, self.y);
		            
		        }
		        
		    } else {
		    	
		        self.avatarContext2D.drawImage(image, self.x, self.y);
		        
		    }
		    
		    /*self.avatarContext2D.drawImage(image, self.x, self.y);*/

		    /* se for repouso = true, se for visema... = false */
		    var flagMouth = true;
		    
		    if(self.indice < self.arrayVisemas.length) {
		    	
		        mouth.src = M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/mouths/' + self.arrayVisemas[self.indice];
		        flagMouth = false;
		        
		    } else {
		    	
		        mouth.src = M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/mouths/repouso.png';
		        flagMouth = true;
		        
		    }
		    
		    if(self.played == false) {
		    	
		        /* workaround para manter a boca em repouso quando piscar em repouso... */
		        mouth.src = M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/mouths/repouso.png';
		        flagMouth = true;
		        
		    }

		    if(!self.flagIE9) {
		    	
		        if(flagMouth) {
		        	
		            self.avatarContext2D.drawImage(self.cacheMouths[0], self.x, self.y);
		            
		        } else {
		        	
		            self.avatarContext2D.drawImage(self.cacheMouths[self.indice+1], self.x, self.y);
		            
		        }
		        
		    } else {
		    	
		        self.avatarContext2D.drawImage(mouth, self.x, self.y);
		        
		    }
		    
		    var piscada = Math.floor((Math.random()*600)+1);

		    if(self.played == true) {
		    	
		        /* duracao de cada visema com o avatar falando */
		        /*
		         * preciso verificar qual é o último parametro do arrayTempo, pois da problema....
		         */
		        if(self.indice < self.arrayTempos.length){
		        	
		            self.sleep(self.arrayTempos[self.indice]+2);
		            
		        } else {
		        	
		            self.played = false;
		            self.indice = 0;
		            
		            if((self.indice % 17) == 0 && self.indice != 0) {
		            	
		                self.sleep(200);
		                
		            } else {
		            	
		            	self.sleep(piscada);
		            	
		            }
		            
		        }
		        
		    } else {
		    	
		        /* duracao das piscadas com o avatar em repouso */
		        if((self.indice % 17) == 0 && self.indice != 0) {
		        	
		        	self.sleep(200);
		        	
		        } else {
		        	
		        	self.sleep(piscada);
		        	
		        }
		        
		    }
		    
		},
		
		processVisems : function(parameter){
			
			var self = this;
			
			var currentVisem;
			
		    self.arrayVisemas = new Array();
		    self.arrayTempos = new Array();
		    
		    var arrayVisemsTemp = new Array();

		    if(parameter ==1) {
		    	arrayVisemsTemp = self.visemsNotifications.split('*');
		    } else if (parameter == 2 || parameter == 3) {
		    	arrayVisemsTemp = self.visemsMessage.split('*');
		    }

		    /*
		     * Aqui o length é -1, pq em todos os .vis, a última linha, é vazia e não nos interessa
		     */
		    for(i=0; i < arrayVisemsTemp.length - 1; i++){
		    	
		    	currentVisem = arrayVisemsTemp[i].split(' ');
		        self.arrayTempos[i] = parseInt(currentVisem[1]) - parseInt(currentVisem[0]);
		        self.arrayVisemas[i] = currentVisem[2];
		    }
		 
		 
		    /*
		    * As imagens dos visemas são carregadas antes de serem usadas.
		    * Foi feito isso, porquê no servidor da locaweb, o chrome tinha problemas para carregar.
		    * A partir disso, também foi solucionado o problema de pisca-pisca nas imagens.
		    * Aqui só carrega, exatamente, os visemas que serão utilizados pelo avatar atual
		    */
		    for(i=0; i < self.arrayVisemas.length; i++) {
		        var mouthVisemImageCache = new Image();
		        mouthVisemImageCache.src =  M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/mouths/' + self.arrayVisemas[i];
		        self.cacheMouths[i + 1] = mouthVisemImageCache;
		    }
		},

		/*
		 * As imagens dos visemas são carregadas antes de serem usadas.
		 * Foi feito isso, porquê no servidor da locaweb, o chrome tinha problemas para carregar.
		 * A partir disso, também foi solucionado o problema de pisca-pisca nas imagens.
		 * Aqui só carrega, exatamente, os visemas de repouso
		 */
		cacheAvatar : function() {
			
			var self = this;
			
		    var imageTemp1 = new Image();
		    imageTemp1.src = M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/merged/repouso_closed.png'
		    self.cacheMerged[0] = imageTemp1;
		    
		    var imageTemp2 = new Image();
		    imageTemp2.src = M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/merged/repouso.png'
		    self.cacheMerged[1] = imageTemp2;
		    
		    /* só carregar a boca após o avatar imageTemp2.... */
		    imageTemp2.onload = function() {
		        var imageTemp3 = new Image();
		        imageTemp3.src = M.cfg['wwwroot'] + '/blocks/avatar/characters/'+self.avatarGenderFolder+'/mouths/repouso.png'
		        self.cacheMouths[0] = imageTemp3;
		        
		        self.draw();
		    };
		},
		
		playAnimation : function(parameter, user, course) {
			
			var self = this;
			
		    /* evitar que dois áudios executem ao mesmo tempo */
		    self.played = false;
		    
		    /*
		    * Quando o usuário sai da janela com o avatar falando,os visemas param de
		    * executar e só volta a executar quando o usuário volta para a janela.
		    * O problema é que o áudio não para. Quando o usuário volta para a tela, não
		    * tem mais áudio, mas o avata ainda mexe a boca.
		    * Para evitar isso, verificamos se o áudio foi encerrado ou não. Se sim,
		    * reseta os visemas.
		    */
		    if(parameter == 1) {
		    	
		        var a_not = document.getElementById('avatar_audio_notification');
		        a_not.addEventListener("ended", function(e) {
		        	
		            self.played = false;
		            
		        });
		        
		        document.getElementById('avatar_audio_notification').pause();
		        
		        /* evitar que dois áudios executem ao mesmo tempo
		         * se tiver alguma mensagem, pausar a mensagem e parar os visemas */
		        
		        if(document.getElementById('avatar_audio_message_src')) {
		        	
		            document.getElementById('avatar_audio_message').pause();
		            
		        }
		    }
		    
		    if(parameter == 2 || parameter == 3) {
		    	
		        /* evitar visemas executarem sem áudio */        
		        var a_msg = document.getElementById('avatar_audio_message');
		        a_msg.addEventListener("ended", function(e) {
		        	
		            self.played = false;
		            
		        });
		        
		        document.getElementById('avatar_audio_message').currentTime = 0;
		        /* evitar que dois áudios executem ao mesmo tempo
		         * se tiver alguma mensagem, pausar a mensagem e parar os visemas */
		        if(document.getElementById('avatar_audio_notification_src')) {
		        	
		            document.getElementById('avatar_audio_notification').pause();
		            
		        }
		    }

		    /* notifications */
		    if(parameter == 1){
		    	
		        jQuery.ajax({
		            type: "POST",
		            /* contentType: "text/html; charset=iso8859-1", */
		            scriptCharset: "UTF-8",
		            url: M.cfg['wwwroot'] + '/blocks/avatar/synthesizer.php',
		            data: {
		            	userid: self.userid, 
		                courseid: self.courseid,
		                parameter: parameter,
		                phrase: self.notificationPhrase, 
		                speaker: self.speaker
		            },
		            beforeSend: function ( xhr ) {
		            	jQuery("#avatar_audio_controls").hide();
		            	jQuery("#avatar_loading").show();
		            }
		        }).done(function ( msg ) {

		            var arrayRetorno = new Array();
		            arrayRetorno = msg.split('#');
		      
		            /* retorno com sucesso */
		            if(arrayRetorno[0] == 0) {

		            	self.visemsNotifications = arrayRetorno[1];
		                self.processVisems(parameter);

                        document.getElementById('avatar_audio_notification_src').src = self.tempFolder + '/' + self.tempPrefix + 'notifications_user' + user +'_course'+course +'.mp3?n='+Math.floor(Math.random()*100000);
                        document.getElementById('avatar_audio_notification_src').type = "audio/mpeg";
                        
                        document.getElementById('avatar_audio_notification').load();
                        
                        document.getElementById('avatar_audio_notification').addEventListener('canplaythrough', function() { 
	                        
                        		jQuery("#avatar_loading").hide();
                        		jQuery("#avatar_audio_controls").show();
	    		            	
                        		window.clearTimeout(self.timeoutID);
			                    self.played = true;
			                    self.indice = 0;
			                    self.draw();
			                    document.getElementById('avatar_audio_notification').play();
                        	}, false);
		            }

		        });

		    } else if (parameter == 2 || parameter == 3) {
		    	
		        self.processVisems(parameter);
		        window.clearTimeout(self.timeoutID);
		        self.played = true;
		        self.indice = 0;
		        setTimeout(function() {
		        	/* adicionar um pequeno atraso, forçando nao desenhar antes do audio realmente começar */
		        	self.draw();
				}, 200);
		        document.getElementById('avatar_audio_message').play();

		    }
			/*$.ajax({
				url: M.cfg['wwwroot'] + '/blocks/avatar/contador.php?parameter='+parameter+'&user='+user+'&course='+course
		    });*/	
		},
		
		sleep : function(delay) {
			
			var self = this;
			
		    self.timeoutID = setTimeout(self.continueExecution, delay);

		},

		continueExecution : function() {
			
			/* necessary due to setTimeout */
			var self = AvatarUIController;
			
		    /*finish doing things after the pause*/
		    /*incremento*/
		    self.indice++;
		    self.draw();
		},

		defineAvatarGenderFolder : function(gender, avatarid) {
			
			var self = this;
			
			var _gender;
			
			if(gender == 'male') {
				_gender = 'Mas';
			} else {
				_gender = 'Fem';
			}
			
			self.avatarGenderFolder = _gender+avatarid;
		},
		
		/* obsoleted */
		/*block : function(delay) {
			
		    var start = new Date().getTime();
		    while (new Date().getTime() < start + delay);
		},*/
		
		ieHack : function() {
			
			var self = this;
			
			/*
			 * Para exibir corretamente no IE9, temos que usar uma abordagem um pouco diferente.
			 * No IE8 ou versões anteriores, não há suporte para canvas.
			 */
			var flagIE9 = false;    
			if (document.all && window.atob && navigator.appName=='Microsoft Internet Explorer') {
			    flagIE9 = false;
			} else if(navigator.appName == 'Microsoft Internet Explorer') {
			    flagIE9 = true;
			}
			
			return flagIE9;
		}
		
	}); 

});