/**
 * WordPress Global Objects
 */
import { useEffect, useState } from '@wordpress/element';
import BWFBlocksInspectorControl from './blocks-inspector';
import BWFStyle from "./blocks-inspector-style";

const BWFBlocksInspectorControlInner = (props) => {
     const { setAttributes, BWFGeneral } = props;
     return (
         <BWFBlocksInspectorControl {...props}>
             {(tab, data) => {
                 if ('general' === tab) {
                     return <BWFGeneral {...props} />;
                 }
                 // eslint-disable-next-line react-hooks/rules-of-hooks
                //  useEffect(
                //      () => setAttributes({ screen: data.device }),
                //      [data.device]
                //  );
                const [screenType, setScreen] = useState('normal');
                 return <BWFStyle {...props} screen={data.device} screenType={screenType} setScreen={setScreen}/>;
             }}
         </BWFBlocksInspectorControl>
     );
 };
 
 export default BWFBlocksInspectorControlInner;
 