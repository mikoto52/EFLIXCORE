CODENAME-EFLIX
==============

EFLIX Core는 최소한의 기능과 MVC Pattern을 지원하는 PHP Framework입니다.

현재 개발 단계에 있어 다소 불안정할 수 있습니다.

IIS 7/8, Nginx, Apache 2.2/2.4 에서 사용할 수 있습니다.



### TODO

- 차세대 템플릿 엔진(SASHIMI)는 현재 개발중입니다.
- 의존성 트리 기능이 현재 테스트중입니다.
- 차세대 템플릿 엔진에선 .stpl이 아닌 .shml 확장자가 사용될 예정입니다.

### 부가 앱

- 관리자/CMS 앱 - REALISER
- 게시판 앱 - BBS


### 확장형 구조

- EFLIX Core는 Router, Kernel, TemplateEngine, CacheEngine으로 구성되어 있습니다.
- EFLIX App은 EFLIX Core를 확장하는 모듈의 개념입니다.


### EFLIX APP

- 모든 앱은 App\appname 형태의 네임스페이스로 정의됩니다.
- appname.register.php => EFLIX Kernel을 초기화 할때 실행되는 파일입니다.
  트리거 등록, 라우터 등록 등과 같은 용도로 사용됩니다.
- appname.controller.php => EFLIX App의 Controller 클래스입니다.
  클래스명은 Controller 로 정의됩니다.


### Requirement

- PHP >= 5.3 
- MySQL >= 5.1 
- Apache 2.4 
